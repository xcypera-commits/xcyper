<?php
// =============================================
// documentation-unit/pages/review.php
// صفحة مراجعة وتعديل المستندات
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}

// =============================================
// معالجة العمليات (POST requests)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'submit_review':
                // إرسال مراجعة
                $sql = "INSERT INTO document_reviews (
                    document_id, reviewer_id, review_type, status,
                    comments, feedback, checklist, rating, decision,
                    review_date, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['document_id'],
                    $_SESSION['user_id'] ?? 1,
                    $_POST['review_type'] ?? 'technical',
                    'completed',
                    $_POST['comments'] ?? null,
                    $_POST['feedback'] ?? null,
                    json_encode($_POST['checklist'] ?? []),
                    $_POST['rating'] ?? null,
                    $_POST['decision'],
                    date('Y-m-d')
                ]);
                
                // تحديث حالة المستند حسب القرار
                $new_status = ($_POST['decision'] == 'approve') ? 'approved' : 'needs_work';
                $update = $db->prepare("UPDATE documents SET status = ?, reviewed_by = ?, review_date = ? WHERE id = ?");
                $update->execute([$new_status, $_SESSION['user_id'] ?? 1, date('Y-m-d'), $_POST['document_id']]);
                
                logActivity($db, 'review', 'document', $_POST['document_id'], 'مراجعة مستند: ' . $_POST['decision']);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إرسال المراجعة بنجاح';
                break;
                
            case 'add_comment':
                // إضافة تعليق
                $sql = "INSERT INTO document_comments (document_id, user_id, comment, page_number, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['document_id'],
                    $_SESSION['user_id'] ?? 1,
                    $_POST['comment'],
                    $_POST['page_number'] ?? null
                ]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم إضافة التعليق';
                break;
                
            case 'resolve_comment':
                // حل تعليق
                $sql = "UPDATE document_comments SET resolved = 1, resolved_by = ?, resolved_at = NOW() WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_SESSION['user_id'] ?? 1, $_POST['comment_id']]);
                
                $response['success'] = true;
                $response['message'] = '✅ تم حل التعليق';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = '❌ خطأ: ' . $e->getMessage();
    }
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// =============================================
// جلب البيانات من قاعدة البيانات
// =============================================
try {
    // المستندات المنتظرة للمراجعة
    // المستندات المنتظرة للمراجعة - نسخة صحيحة
$pending_docs = $db->query("
    SELECT d.*, 
           p.project_name,
           p.priority as project_priority, 
           u_creator.full_name as creator_name,
           u_review.full_name as reviewer_name,
           (SELECT COUNT(*) FROM document_comments WHERE document_id = d.id AND resolved = 0) as pending_comments
    FROM documents d
    LEFT JOIN documentation_projects p ON d.project_id = p.id
    LEFT JOIN users u_creator ON d.created_by = u_creator.id
    LEFT JOIN users u_review ON d.reviewed_by = u_review.id
    WHERE d.status IN ('under_review', 'needs_work')
    ORDER BY 
        CASE p.priority 
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
            ELSE 5
        END,
        d.updated_at DESC
    LIMIT 20
")->fetchAll();
    
   // =============================================
// إحصائيات المراجعات
// =============================================
try {
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM documents WHERE status = 'under_review') as pending_reviews,
            (SELECT COUNT(*) FROM documents WHERE status = 'needs_work') as needs_work,
            (SELECT COUNT(*) FROM document_reviews WHERE DATE(created_at) = CURDATE()) as today_reviews,
            (SELECT COUNT(*) FROM document_reviews WHERE reviewer_id = ? AND DATE(created_at) = CURDATE()) as my_reviews,
            (SELECT COUNT(*) FROM document_comments WHERE resolved = 0) as pending_comments
    ");
    $stmt->execute([$_SESSION['user_id'] ?? 1]);
    $stats = $stmt->fetch();
    
    // إذا الاستعلام رجع null، نستخدم array افتراضية
    if (!$stats) {
        $stats = [
            'pending_reviews' => 0,
            'needs_work' => 0,
            'today_reviews' => 0,
            'my_reviews' => 0,
            'pending_comments' => 0
        ];
    }
} catch (Exception $e) {
    // في حالة خطأ، نستخدم array افتراضية
    $stats = [
        'pending_reviews' => 0,
        'needs_work' => 0,
        'today_reviews' => 0,
        'my_reviews' => 0,
        'pending_comments' => 0
    ];
    error_log("خطأ في إحصائيات المراجعات: " . $e->getMessage());
}
    // آخر المراجعات
    $recent_reviews = $db->query("
        SELECT r.*, 
               d.title as document_title,
               d.document_code,
               u.full_name as reviewer_name,
               u2.full_name as creator_name
        FROM document_reviews r
        LEFT JOIN documents d ON r.document_id = d.id
        LEFT JOIN users u ON r.reviewer_id = u.id
        LEFT JOIN users u2 ON d.created_by = u2.id
        ORDER BY r.created_at DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (Exception $e) {
    $pending_docs = [];
    $recent_reviews = [];
    $stats = [
        'pending_reviews' => 0,
        'needs_work' => 0,
        'today_reviews' => 0,
        'my_reviews' => 0,
        'pending_comments' => 0
    ];
    
    echo '<div class="bg-red-600 p-4 rounded-lg mb-4 text-center">❌ خطأ في جلب البيانات: ' . $e->getMessage() . '</div>';
}

// =============================================
// إذا كان في طلب عرض مستند للمراجعة
// =============================================
$current_doc = null;
$doc_comments = [];
$doc_reviews = [];

if (isset($_GET['doc']) && !empty($_GET['doc'])) {
    $doc_id = (int)$_GET['doc'];
    
    try {
        // جلب المستند
        $stmt = $db->prepare("
            SELECT d.*, 
                   p.project_name,
                   u_creator.full_name as creator_name,
                   u_review.full_name as reviewer_name
            FROM documents d
            LEFT JOIN documentation_projects p ON d.project_id = p.id
            LEFT JOIN users u_creator ON d.created_by = u_creator.id
            LEFT JOIN users u_review ON d.reviewed_by = u_review.id
            WHERE d.id = ?
        ");
        $stmt->execute([$doc_id]);
        $current_doc = $stmt->fetch();
        
        if ($current_doc) {
            // جلب تعليقات المستند
            $stmt = $db->prepare("
                SELECT c.*, u.full_name as user_name, u.id as user_id
                FROM document_comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.document_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$doc_id]);
            $doc_comments = $stmt->fetchAll();
            
            // جلب مراجعات المستند السابقة
            $stmt = $db->prepare("
                SELECT r.*, u.full_name as reviewer_name
                FROM document_reviews r
                LEFT JOIN users u ON r.reviewer_id = u.id
                WHERE r.document_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$doc_id]);
            $doc_reviews = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        echo '<div class="bg-red-600 p-4 rounded-lg mb-4">❌ خطأ في جلب المستند: ' . $e->getMessage() . '</div>';
    }
}

// دوال مساعدة
function getReviewTypeText($type) {
    $texts = [
        'technical' => 'مراجعة تقنية',
        'security' => 'مراجعة أمنية',
        'quality' => 'مراجعة جودة',
        'final' => 'مراجعة نهائية'
    ];
    return $texts[$type] ?? $type;
}

function getDecisionBadge($decision) {
    $classes = [
        'approve' => 'bg-green-600 bg-opacity-20 text-green-400',
        'rework' => 'bg-yellow-600 bg-opacity-20 text-yellow-400',
        'reject' => 'bg-red-600 bg-opacity-20 text-red-400',
        'pending' => 'bg-gray-600 bg-opacity-20 text-gray-400'
    ];
    
    $texts = [
        'approve' => '✅ اعتماد',
        'rework' => '🔄 إعادة عمل',
        'reject' => '❌ رفض',
        'pending' => '⏳ معلق'
    ];
    
    $class = $classes[$decision] ?? 'bg-gray-600 bg-opacity-20 text-gray-400';
    $text = $texts[$decision] ?? $decision;
    
    return "<span class='px-3 py-1 rounded-full text-xs font-semibold $class'>$text</span>";
}
?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري التحميل...</p>
    </div>
</div>

<!-- ============================================= -->
<?php if ($current_doc): ?>
<!-- ============================================= -->
<!-- وضع مراجعة مستند محدد -->
<!-- ============================================= -->

<!-- شريط التنقل -->
<div class="flex items-center justify-between mb-6">
    <button onclick="window.location.href='?page=review'" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
        </svg>
        العودة لقائمة المراجعات
    </button>
    
    <h2 class="text-2xl font-bold text-right">مراجعة المستند</h2>
</div>

<!-- معلومات المستند -->
<div class="cyber-border bg-slate-800 rounded-xl p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <span class="text-sm text-gray-400"><?php echo $current_doc['document_code']; ?></span>
            <span class="mx-2 text-gray-600">|</span>
            <span class="text-sm text-gray-400">الإصدار: v<?php echo $current_doc['version']; ?></span>
        </div>
        <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($current_doc['title']); ?></h1>
    </div>
    
    <div class="grid grid-cols-4 gap-4 mt-4 text-sm">
        <div>
            <p class="text-gray-400">المشروع</p>
            <p class="font-semibold"><?php echo htmlspecialchars($current_doc['project_name'] ?? 'غير محدد'); ?></p>
        </div>
        <div>
            <p class="text-gray-400">الكاتب</p>
            <p class="font-semibold"><?php echo htmlspecialchars($current_doc['creator_name'] ?? 'غير محدد'); ?></p>
        </div>
        <div>
            <p class="text-gray-400">تاريخ الإنشاء</p>
            <p class="font-semibold"><?php echo date('Y-m-d', strtotime($current_doc['created_date'])); ?></p>
        </div>
        <div>
            <p class="text-gray-400">آخر تحديث</p>
            <p class="font-semibold"><?php echo timeAgo($current_doc['updated_at']); ?></p>
        </div>
    </div>
</div>

<!-- محتوى المراجعة -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- المحتوى الأساسي -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- محتوى المستند -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                محتوى المستند
            </h3>
            
            <div id="document-content" class="prose prose-invert max-w-none p-4 bg-slate-900 rounded-lg">
                <?php echo $current_doc['content'] ?? '<p class="text-gray-400">لا يوجد محتوى</p>'; ?>
            </div>
        </div>
        
        <!-- التعليقات -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                التعليقات (<?php echo count($doc_comments); ?>)
            </h3>
            
            <div id="comments-list" class="space-y-4 mb-6">
                <?php if (empty($doc_comments)): ?>
                    <p class="text-gray-400 text-center py-4">لا توجد تعليقات بعد</p>
                <?php else: ?>
                    <?php foreach ($doc_comments as $comment): ?>
                    <div class="bg-slate-900 rounded-lg p-4 <?php echo $comment['resolved'] ? 'opacity-50' : ''; ?>" id="comment-<?php echo $comment['id']; ?>">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <?php if ($comment['resolved']): ?>
                                    <span class="text-xs bg-green-600 bg-opacity-20 text-green-400 px-2 py-1 rounded-full">تم الحل</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center">
                                <span class="text-sm font-semibold ml-2"><?php echo htmlspecialchars($comment['user_name'] ?? 'مستخدم'); ?></span>
                                <span class="text-xs text-gray-400"><?php echo timeAgo($comment['created_at']); ?></span>
                            </div>
                        </div>
                        
                        <p class="text-gray-300 mb-2"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        
                        <?php if ($comment['page_number']): ?>
                            <p class="text-xs text-gray-500 mb-2">الصفحة: <?php echo $comment['page_number']; ?></p>
                        <?php endif; ?>
                        
                        <?php if (!$comment['resolved'] && ($_SESSION['user_id'] ?? 0) == $comment['user_id']): ?>
                        <div class="flex items-center justify-end">
                            <button onclick="resolveComment(<?php echo $comment['id']; ?>)" class="text-xs text-green-400 hover:text-green-300">
                                حل التعليق
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- إضافة تعليق جديد -->
            <form id="comment-form" onsubmit="addReviewComment(event, <?php echo $current_doc['id']; ?>)">
                <textarea id="new-comment" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right" placeholder="أضف تعليقك على المستند..."></textarea>
                <div class="flex items-center justify-between mt-2">
                    <input type="number" id="comment-page" placeholder="رقم الصفحة (اختياري)" class="w-32 px-3 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right text-sm">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                        إضافة تعليق
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- الشريط الجانبي - أدوات المراجعة -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- بطاقة إرسال المراجعة -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                إرسال المراجعة
            </h3>
            
            <form id="review-form" onsubmit="submitReview(event, <?php echo $current_doc['id']; ?>)">
                
                <!-- نوع المراجعة -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-right">نوع المراجعة</label>
                    <select id="review-type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500">
                        <option value="technical">مراجعة تقنية</option>
                        <option value="security">مراجعة أمنية</option>
                        <option value="quality">مراجعة جودة</option>
                        <option value="final">مراجعة نهائية</option>
                    </select>
                </div>
                
                <!-- قائمة التحقق -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-right">قائمة التحقق</label>
                    <div class="space-y-2">
                        <label class="flex items-center justify-end">
                            <span class="text-sm text-gray-300 ml-2">اكتمال المحتوى</span>
                            <input type="checkbox" class="checklist-item w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                        <label class="flex items-center justify-end">
                            <span class="text-sm text-gray-300 ml-2">دقة المعلومات</span>
                            <input type="checkbox" class="checklist-item w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                        <label class="flex items-center justify-end">
                            <span class="text-sm text-gray-300 ml-2">وضوح الشرح</span>
                            <input type="checkbox" class="checklist-item w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                        <label class="flex items-center justify-end">
                            <span class="text-sm text-gray-300 ml-2">التزام بالمعايير</span>
                            <input type="checkbox" class="checklist-item w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded">
                        </label>
                    </div>
                </div>
                
                <!-- التقييم بالنجوم -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-right">التقييم</label>
                    <div class="flex items-center justify-end space-x-1 space-x-reverse">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" onclick="setRating(<?php echo $i; ?>)" class="rating-star text-2xl text-gray-500 hover:text-yellow-400 transition-colors">
                            ★
                        </button>
                        <?php endfor; ?>
                        <input type="hidden" id="rating-value" value="0">
                    </div>
                </div>
                
                <!-- تعليق المراجعة -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-right">ملاحظات المراجعة</label>
                    <textarea id="review-comments" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right" placeholder="أضف ملاحظاتك حول المستند..."></textarea>
                </div>
                
                <!-- ملخص التعديلات المطلوبة -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2 text-right">التعديلات المطلوبة</label>
                    <textarea id="review-feedback" rows="3" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right" placeholder="ما الذي يحتاج تعديل؟"></textarea>
                </div>
                
                <!-- أزرار القرار -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <button type="button" onclick="setDecision('approve')" class="py-3 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold transition-all">
                        ✅ اعتماد
                    </button>
                    <button type="button" onclick="setDecision('rework')" class="py-3 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm font-semibold transition-all">
                        🔄 إعادة عمل
                    </button>
                    <button type="button" onclick="setDecision('reject')" class="py-3 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-semibold transition-all">
                        ❌ رفض
                    </button>
                </div>
                
                <input type="hidden" id="review-decision" value="">
                
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    إرسال المراجعة
                </button>
            </form>
        </div>
        
        <!-- مراجعات سابقة -->
        <?php if (!empty($doc_reviews)): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                مراجعات سابقة
            </h3>
            
            <div class="space-y-3">
                <?php foreach ($doc_reviews as $review): ?>
                <div class="bg-slate-900 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <?php echo getDecisionBadge($review['decision']); ?>
                        <span class="text-xs text-gray-400"><?php echo timeAgo($review['created_at']); ?></span>
                    </div>
                    <p class="text-xs text-gray-300 mb-1">المراجع: <?php echo htmlspecialchars($review['reviewer_name'] ?? 'غير معروف'); ?></p>
                    <?php if ($review['rating']): ?>
                    <div class="flex items-center mb-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-600'; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($review['comments']): ?>
                    <p class="text-xs text-gray-400 mt-1"><?php echo nl2br(htmlspecialchars($review['comments'])); ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================= -->
<?php else: ?>
<!-- ============================================= -->
<!-- وضع قائمة المستندات المنتظرة -->
<!-- ============================================= -->

<!-- إحصائيات سريعة -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مستندات للمراجعة</p>
        <p class="text-2xl font-bold text-yellow-400"><?php echo $stats['pending_reviews']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">بحاجة لتعديل</p>
        <p class="text-2xl font-bold text-orange-400"><?php echo $stats['needs_work']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مراجعات اليوم</p>
        <p class="text-2xl font-bold text-green-400"><?php echo $stats['today_reviews']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">مراجعاتي</p>
        <p class="text-2xl font-bold text-blue-400"><?php echo $stats['my_reviews']; ?></p>
    </div>
    
    <div class="cyber-border bg-slate-800 rounded-lg p-4">
        <p class="text-sm text-gray-400 mb-1">تعليقات معلقة</p>
        <p class="text-2xl font-bold text-red-400"><?php echo $stats['pending_comments']; ?></p>
    </div>
</div>

<!-- قائمة المستندات -->
<div class="cyber-border bg-slate-800 rounded-xl p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-right">المستندات المنتظرة للمراجعة</h3>
        
        <div class="flex items-center space-x-2 space-x-reverse">
            <select id="filter-status" onchange="filterDocuments()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
                <option value="all">جميع المستندات</option>
                <option value="under_review">قيد المراجعة</option>
                <option value="needs_work">بحاجة تعديل</option>
            </select>
            
            <select id="sort-by" onchange="filterDocuments()" class="px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg text-sm">
                <option value="priority">حسب الأولوية</option>
                <option value="date">حسب التاريخ</option>
                <option value="comments">حسب التعليقات</option>
            </select>
        </div>
    </div>
    
    <?php if (empty($pending_docs)): ?>
        <div class="text-center py-12">
            <svg class="w-24 h-24 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-400 mb-2">لا توجد مستندات للمراجعة</h3>
            <p class="text-gray-500">كل المستندات تمت مراجعتها ✓</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pending_docs as $doc): ?>
            <div class="bg-slate-900 rounded-lg p-4 hover:bg-slate-800 transition-colors cursor-pointer" onclick="window.location.href='?page=review&doc=<?php echo $doc['id']; ?>'">
                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <?php if ($doc['pending_comments'] > 0): ?>
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs text-white">
                            <?php echo $doc['pending_comments']; ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-sm text-gray-400"><?php echo $doc['document_code']; ?></span>
                    </div>
                    
                    <div class="flex-1 mr-4">
                        <h4 class="font-bold mb-1"><?php echo htmlspecialchars($doc['title']); ?></h4>
                        <div class="flex items-center text-sm text-gray-400 space-x-4 space-x-reverse">
                            <span>المشروع: <?php echo htmlspecialchars($doc['project_name'] ?? 'غير محدد'); ?></span>
                            <span>الكاتب: <?php echo htmlspecialchars($doc['creator_name'] ?? 'غير معروف'); ?></span>
                            <span>آخر تحديث: <?php echo timeAgo($doc['updated_at']); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <?php if ($doc['status'] == 'under_review'): ?>
                        <span class="px-3 py-1 bg-yellow-600 bg-opacity-20 text-yellow-400 rounded-full text-xs">
                            قيد المراجعة
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-orange-600 bg-opacity-20 text-orange-400 rounded-full text-xs">
                            بحاجة تعديل
                        </span>
                        <?php endif; ?>
                        
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- آخر المراجعات -->
<?php if (!empty($recent_reviews)): ?>
<div class="cyber-border bg-slate-800 rounded-xl p-6 mt-6">
    <h3 class="text-xl font-bold mb-4 text-right">آخر المراجعات</h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="table-header text-right">
                    <th class="px-4 py-3">القرار</th>
                    <th class="px-4 py-3">التقييم</th>
                    <th class="px-4 py-3">المراجع</th>
                    <th class="px-4 py-3">التاريخ</th>
                    <th class="px-4 py-3">المستند</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_reviews as $review): ?>
                <tr class="border-b border-slate-700">
                    <td class="px-4 py-3"><?php echo getDecisionBadge($review['decision']); ?></td>
                    <td class="px-4 py-3">
                        <?php if ($review['rating']): ?>
                        <div class="flex items-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-600'; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3"><?php echo htmlspecialchars($review['reviewer_name'] ?? 'النظام'); ?></td>
                    <td class="px-4 py-3"><?php echo timeAgo($review['created_at']); ?></td>
                    <td class="px-4 py-3">
                        <a href="?page=review&doc=<?php echo $review['document_id']; ?>" class="text-blue-400 hover:text-blue-300">
                            <?php echo htmlspecialchars(mb_substr($review['document_title'], 0, 30)) . '...'; ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ============================================= -->
<!-- JavaScript الخاص بالصفحة -->
<!-- ============================================= -->
<script>
// =============================================
// دوال المراجعة
// =============================================

let currentRating = 0;
let currentDecision = '';

function setRating(rating) {
    currentRating = rating;
    document.getElementById('rating-value').value = rating;
    
    // تحديث النجوم
    document.querySelectorAll('.rating-star').forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-gray-500');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-500');
        }
    });
}

function setDecision(decision) {
    currentDecision = decision;
    document.getElementById('review-decision').value = decision;
    
    // تحديث الأزرار
    document.querySelectorAll('.grid button').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-offset-2', 'ring-offset-slate-800');
    });
    
    if (decision === 'approve') {
        document.querySelector('button[onclick="setDecision(\'approve\')"]').classList.add('ring-2', 'ring-green-400', 'ring-offset-2', 'ring-offset-slate-800');
    } else if (decision === 'rework') {
        document.querySelector('button[onclick="setDecision(\'rework\')"]').classList.add('ring-2', 'ring-yellow-400', 'ring-offset-2', 'ring-offset-slate-800');
    } else if (decision === 'reject') {
        document.querySelector('button[onclick="setDecision(\'reject\')"]').classList.add('ring-2', 'ring-red-400', 'ring-offset-2', 'ring-offset-slate-800');
    }
}

function submitReview(event, docId) {
    event.preventDefault();
    
    if (!currentDecision) {
        showNotification('❌ الرجاء اختيار قرار المراجعة', 'error');
        return;
    }
    
    // جمع قائمة التحقق
    const checklist = [];
    document.querySelectorAll('.checklist-item').forEach(item => {
        checklist.push(item.checked);
    });
    
    const formData = new FormData();
    formData.append('action', 'submit_review');
    formData.append('document_id', docId);
    formData.append('review_type', document.getElementById('review-type').value);
    formData.append('comments', document.getElementById('review-comments').value);
    formData.append('feedback', document.getElementById('review-feedback').value);
    formData.append('checklist', JSON.stringify(checklist));
    formData.append('rating', currentRating);
    formData.append('decision', currentDecision);
    
    showLoading();
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                window.location.href = '?page=review';
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('❌ حدث خطأ في الاتصال', 'error');
        console.error(error);
    });
}

function addReviewComment(event, docId) {
    event.preventDefault();
    
    const comment = document.getElementById('new-comment').value;
    const page = document.getElementById('comment-page').value;
    
    if (!comment.trim()) {
        showNotification('❌ الرجاء إدخال التعليق', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_comment');
    formData.append('document_id', docId);
    formData.append('comment', comment);
    formData.append('page_number', page);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('new-comment').value = '';
            document.getElementById('comment-page').value = '';
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('❌ حدث خطأ', 'error');
        console.error(error);
    });
}

function resolveComment(commentId) {
    if (confirm('هل أنت متأكد من حل هذا التعليق؟')) {
        const formData = new FormData();
        formData.append('action', 'resolve_comment');
        formData.append('comment_id', commentId);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('❌ حدث خطأ', 'error');
            console.error(error);
        });
    }
}

function filterDocuments() {
    const status = document.getElementById('filter-status').value;
    const sort = document.getElementById('sort-by').value;
    
    // إعادة تحميل الصفحة مع الفلاتر
    window.location.href = '?page=review&status=' + status + '&sort=' + sort;
}

// =============================================
// دوال مساعدة
// =============================================
function showNotification(message, type) {
    const container = document.getElementById('notification-container');
    const colors = {
        'success': 'bg-green-600',
        'error': 'bg-red-600',
        'info': 'bg-blue-600',
        'warning': 'bg-yellow-600'
    };
    
    const icons = {
        'success': '✅',
        'error': '❌',
        'info': 'ℹ️',
        'warning': '⚠️'
    };
    
    const notification = document.createElement('div');
    notification.className = `notification ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-sm`;
    notification.innerHTML = `<div class="flex items-center"><span class="ml-3">${icons[type]}</span><span>${message}</span></div>`;
    
    container.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function showLoading() {
    document.getElementById('loading-spinner').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-spinner').classList.add('hidden');
}
</script>

<!-- ============================================= -->
<!-- CSS إضافي -->
<!-- ============================================= -->
<style>
.prose {
    max-width: none;
}
.prose h1 { font-size: 2rem; font-weight: bold; margin-bottom: 1rem; }
.prose h2 { font-size: 1.5rem; font-weight: bold; margin: 1.5rem 0 0.75rem; }
.prose h3 { font-size: 1.25rem; font-weight: bold; margin: 1.25rem 0 0.5rem; }
.prose p { margin-bottom: 1rem; line-height: 1.6; }
.prose ul, .prose ol { margin: 1rem 0; padding-right: 2rem; }
.prose li { margin-bottom: 0.25rem; }
.prose pre { background: #0f172a; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; overflow-x: auto; }
.prose code { font-family: monospace; color: #e2e8f0; }
.prose a { color: #3b82f6; text-decoration: underline; }

.modal-backdrop {
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

.spinner {
    border: 3px solid rgba(59, 130, 246, 0.3);
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.notification {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.table-header {
    background: linear-gradient(90deg, #1e293b 0%, #334155 100%);
}

.rating-star {
    cursor: pointer;
    transition: all 0.2s ease;
}

.rating-star:hover {
    transform: scale(1.2);
}
</style>