<?php
// =============================================
// documentation-unit/pages/creation.php
// صفحة إنشاء وتحرير المستندات الفنية
// =============================================

// التأكد من وجود اتصال قاعدة البيانات
if (!isset($db)) {
    echo '<div class="bg-red-600 text-white p-4 rounded-lg text-center font-bold text-xl">❌ خطأ: قاعدة البيانات غير متصلة</div>';
    return;
}
// =============================================
// جلب البيانات الأساسية
// =============================================
try {
    // جلب المشاريع النشطة
    $projects = $db->query("
        SELECT id, project_name, project_code 
        FROM documentation_projects 
        WHERE status IN ('new', 'in_progress', 'under_analysis')
        ORDER BY project_name
    ")->fetchAll();
    
    // ✅ جلب القوالب - بشكل صحيح
    $stmt = $db->prepare("
        SELECT id, name, type, description 
        FROM document_templates 
        WHERE is_public = 1 OR created_by = ?
        ORDER BY usage_count DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'] ?? 1]);
    $templates = $stmt->fetchAll();
    
    // ✅ جلب آخر المستندات للمستخدم - بشكل صحيح
    $stmt = $db->prepare("
        SELECT id, title, document_code, status, updated_at
        FROM documents
        WHERE created_by = ?
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id'] ?? 1]);
    $recent_docs = $stmt->fetchAll();
    
    // جلب الوسوم الشائعة - هذا استعلام بسيط بدون parameters
    $popular_tags = $db->query("
        SELECT t.name, COUNT(dt.document_id) as usage_count
        FROM tags t
        LEFT JOIN document_tags dt ON t.id = dt.tag_id
        GROUP BY t.id
        ORDER BY usage_count DESC
        LIMIT 10
    ")->fetchAll();
    
} catch (Exception $e) {
    error_log("خطأ في جلب البيانات: " . $e->getMessage());
    $projects = [];
    $templates = [];
    $recent_docs = [];
    $popular_tags = [];
}// =============================================
// معالجة طلب تحميل مستند للتحرير
// =============================================
$document = null;
$edit_mode = false;
$document_id = $_GET['edit'] ?? $_GET['template'] ?? null;

if ($document_id) {
    try {
        if (isset($_GET['edit'])) {
            // ✅ تحرير مستند موجود - بشكل صحيح
            $stmt = $db->prepare("
                SELECT d.*, p.project_name 
                FROM documents d
                LEFT JOIN documentation_projects p ON d.project_id = p.id
                WHERE d.id = ?
            ");
            $stmt->execute([$document_id]);
            $document = $stmt->fetch();
            
            if ($document) {
                $edit_mode = true;
                // ✅ جلب إصدارات المستند - بشكل صحيح
                $stmt2 = $db->prepare("
                    SELECT version_number, changes, created_at 
                    FROM document_versions 
                    WHERE document_id = ? 
                    ORDER BY created_at DESC
                ");
                $stmt2->execute([$document_id]);
                $document['versions'] = $stmt2->fetchAll();
            }
        } elseif (isset($_GET['template'])) {
            // ✅ استخدام قالب - بشكل صحيح
            $stmt = $db->prepare("SELECT * FROM document_templates WHERE id = ?");
            $stmt->execute([$document_id]);
            $template = $stmt->fetch();
            
            if ($template) {
                // تحويل القالب إلى مستند جديد
                $document = [
                    'title' => $template['name'],
                    'content' => $template['structure'] ?? '',
                    'document_type' => $template['type'],
                    'format' => $template['format'],
                    'is_template' => true
                ];
                // ✅ تحديث عدد استخدامات القالب - بشكل صحيح
                $stmt2 = $db->prepare("UPDATE document_templates SET usage_count = usage_count + 1 WHERE id = ?");
                $stmt2->execute([$document_id]);
            }
        }
    } catch (Exception $e) {
        error_log("خطأ في جلب المستند: " . $e->getMessage());
    }
}

// دوال مساعدة
function getEditorConfig($type) {
    $configs = [
        'technical' => [
            'placeholder' => 'اكتب التوثيق التقني هنا...',
            'sections' => ['مقدمة', 'المتطلبات', 'التصميم', 'التنفيذ', 'الاختبار', 'الاستنتاج']
        ],
        'security' => [
            'placeholder' => 'اكتب التوثيق الأمني هنا...',
            'sections' => ['نطاق التقييم', 'المنهجية', 'الثغرات المكتشفة', 'التوصيات', 'خطة المعالجة']
        ],
        'api' => [
            'placeholder' => 'اكتب توثيق API هنا...',
            'sections' => ['نظرة عامة', 'المصادقة', 'ال endpoints', 'الأمثلة', 'الأخطاء']
        ],
        'user_guide' => [
            'placeholder' => 'اكتب دليل المستخدم هنا...',
            'sections' => ['مقدمة', 'بدء الاستخدام', 'الميزات', 'استكشاف الأخطاء', 'الأسئلة الشائعة']
        ]
    ];
    
    return $configs[$type] ?? $configs['technical'];
}
?>

<!-- ============================================= -->
<!-- حاوية الإشعارات ومؤشر التحميل -->
<!-- ============================================= -->
<div id="notification-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

<div id="loading-spinner" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="text-center">
        <div class="spinner mx-auto mb-4"></div>
        <p class="text-gray-400">جاري الحفظ...</p>
    </div>
</div>

<!-- ============================================= -->
<!-- رأس الصفحة -->
<!-- ============================================= -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-2 space-x-reverse">
        <button onclick="window.location.href='?page=documents'" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm flex items-center">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
            العودة للمستندات
        </button>
        
        <button onclick="saveDocument()" class="px-6 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold flex items-center">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            حفظ المستند
        </button>
        
        <button onclick="previewDocument()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm flex items-center">
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            معاينة
        </button>
    </div>
    
    <h2 class="text-2xl font-bold text-right">
        <?php echo $edit_mode ? '✏️ تعديل المستند' : (isset($_GET['template']) ? '📋 استخدام قالب' : '➕ إنشاء توثيق فني جديد'); ?>
    </h2>
</div>

<!-- ============================================= -->
<!-- المحرر الرئيسي -->
<!-- ============================================= -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    
    <!-- ============================================= -->
    <!-- الشريط الجانبي - معلومات المستند -->
    <!-- ============================================= -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- بطاقة المعلومات الأساسية -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                معلومات المستند
            </h3>
            
            <form id="document-info-form" class="space-y-4">
                <input type="hidden" id="document-id" value="<?php echo $document['id'] ?? ''; ?>">
                
                <!-- عنوان المستند -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">عنوان المستند <span class="text-red-400">*</span></label>
                    <input type="text" id="doc-title" value="<?php echo htmlspecialchars($document['title'] ?? ''); ?>" 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="أدخل عنوان المستند">
                </div>
                
                <!-- المشروع -->
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">المشروع</label>
                    <select id="doc-project" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                        <option value="">-- بدون مشروع --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo ($document['project_id'] ?? '') == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- نوع المستند والتنسيق -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">النوع</label>
                        <select id="doc-type" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="technical" <?php echo ($document['document_type'] ?? '') == 'technical' ? 'selected' : ''; ?>>تقني</option>
                            <option value="security" <?php echo ($document['document_type'] ?? '') == 'security' ? 'selected' : ''; ?>>أمني</option>
                            <option value="api" <?php echo ($document['document_type'] ?? '') == 'api' ? 'selected' : ''; ?>>API</option>
                            <option value="user_guide" <?php echo ($document['document_type'] ?? '') == 'user_guide' ? 'selected' : ''; ?>>دليل مستخدم</option>
                            <option value="requirements" <?php echo ($document['document_type'] ?? '') == 'requirements' ? 'selected' : ''; ?>>متطلبات</option>
                            <option value="report" <?php echo ($document['document_type'] ?? '') == 'report' ? 'selected' : ''; ?>>تقرير</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">التنسيق</label>
                        <select id="doc-format" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="pdf" <?php echo ($document['format'] ?? '') == 'pdf' ? 'selected' : ''; ?>>PDF</option>
                            <option value="docx" <?php echo ($document['format'] ?? '') == 'docx' ? 'selected' : ''; ?>>DOCX</option>
                            <option value="md" <?php echo ($document['format'] ?? '') == 'md' ? 'selected' : ''; ?>>Markdown</option>
                            <option value="html" <?php echo ($document['format'] ?? '') == 'html' ? 'selected' : ''; ?>>HTML</option>
                        </select>
                    </div>
                </div>
                
                <!-- الإصدار والحالة -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الإصدار</label>
                        <input type="text" id="doc-version" value="<?php echo $document['version'] ?? '1.0.0'; ?>" 
                               class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-right">الحالة</label>
                        <select id="doc-status" class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500">
                            <option value="draft" <?php echo ($document['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>مسودة</option>
                            <option value="under_review" <?php echo ($document['status'] ?? '') == 'under_review' ? 'selected' : ''; ?>>قيد المراجعة</option>
                            <option value="approved" <?php echo ($document['status'] ?? '') == 'approved' ? 'selected' : ''; ?>>معتمد</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- بطاقة القوالب -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                القوالب المتاحة
            </h3>
            
            <?php if (empty($templates)): ?>
                <p class="text-gray-400 text-sm text-center py-4">لا توجد قوالب متاحة</p>
            <?php else: ?>
                <div class="space-y-2 max-h-60 overflow-y-auto scrollbar-custom">
                    <?php foreach ($templates as $template): ?>
                    <div class="template-item p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors cursor-pointer"
                         onclick="loadTemplate(<?php echo $template['id']; ?>)">
                        <div class="flex items-center justify-between">
                            <span class="text-xs px-2 py-1 bg-blue-600 bg-opacity-20 text-blue-400 rounded-full">
                                <?php echo $template['type']; ?>
                            </span>
                            <span class="font-semibold text-sm"><?php echo htmlspecialchars($template['name']); ?></span>
                        </div>
                        <?php if ($template['description']): ?>
                            <p class="text-xs text-gray-400 mt-2"><?php echo htmlspecialchars($template['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <button onclick="window.location.href='?page=templates'" class="w-full mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-sm">
                استعراض كل القوالب
            </button>
        </div>
        
        <!-- بطاقة الوسوم -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l5 5a2 2 0 01.586 1.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                </svg>
                الوسوم
            </h3>
            
            <div id="tags-container" class="flex flex-wrap gap-2 mb-4">
                <!-- الوسوم المضافة تظهر هنا -->
            </div>
            
            <div class="flex items-center gap-2">
                <input type="text" id="tag-input" placeholder="أضف وسماً..." 
                       class="flex-1 px-4 py-2 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-yellow-500 text-right text-sm"
                       onkeypress="if(event.key==='Enter') addTag()">
                <button onclick="addTag()" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded-lg text-sm">
                    إضافة
                </button>
            </div>
            
            <?php if (!empty($popular_tags)): ?>
            <div class="mt-4">
                <p class="text-sm text-gray-400 mb-2">وسوم شائعة:</p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($popular_tags as $tag): ?>
                    <span onclick="addTagByName('<?php echo $tag['name']; ?>')" 
                          class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded-full text-xs cursor-pointer transition-colors">
                        #<?php echo htmlspecialchars($tag['name']); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- بطاقة المستندات الأخيرة -->
        <?php if (!empty($recent_docs) && !$edit_mode): ?>
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                آخر مستنداتك
            </h3>
            
            <div class="space-y-2">
                <?php foreach ($recent_docs as $doc): ?>
                <a href="?page=creation&edit=<?php echo $doc['id']; ?>" 
                   class="block p-3 bg-slate-900 rounded-lg hover:bg-slate-800 transition-colors">
                    <p class="font-semibold text-sm"><?php echo htmlspecialchars($doc['title']); ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?php echo $doc['document_code']; ?> • <?php echo timeAgo($doc['updated_at']); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- ============================================= -->
    <!-- منطقة المحرر الرئيسية -->
    <!-- ============================================= -->
    <div class="lg:col-span-3">
        <div class="cyber-border bg-slate-800 rounded-xl p-6">
            
            <!-- شريط أدوات التحرير -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-700">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button onclick="formatText('bold')" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="عريض">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <text x="7" y="17" font-weight="bold" font-size="14" fill="currentColor">ب</text>
                        </svg>
                    </button>
                    <button onclick="formatText('italic')" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="مائل">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <text x="8" y="17" font-style="italic" font-size="14" fill="currentColor">م</text>
                        </svg>
                    </button>
                    <button onclick="formatText('underline')" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="تسطير">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <text x="7" y="17" text-decoration="underline" font-size="14" fill="currentColor">س</text>
                        </svg>
                    </button>
                    <span class="w-px h-6 bg-slate-600 mx-2"></span>
                    <button onclick="insertHeading(1)" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-bold">H1</button>
                    <button onclick="insertHeading(2)" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-bold">H2</button>
                    <button onclick="insertHeading(3)" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-bold">H3</button>
                    <span class="w-px h-6 bg-slate-600 mx-2"></span>
                    <button onclick="insertList('ul')" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="قائمة نقطية">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                    </button>
                    <button onclick="insertList('ol')" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="قائمة مرقمة">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </button>
                    <span class="w-px h-6 bg-slate-600 mx-2"></span>
                    <button onclick="insertCode()" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="إدراج كود">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </button>
                    <button onclick="insertLink()" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="إدراج رابط">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </button>
                    <button onclick="insertImage()" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="إدراج صورة">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="text-sm text-gray-400" id="word-count">0 كلمة</span>
                    <span class="w-px h-6 bg-slate-600"></span>
                    <button onclick="toggleFullscreen()" class="p-2 bg-slate-700 hover:bg-slate-600 rounded-lg" title="ملء الشاشة">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0 0l-5-5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- المحرر والمعاينة -->
            <div id="editor-container" class="relative">
                <!-- المحرر -->
                <div id="editor" class="editor-content min-h-[500px] max-h-[600px] overflow-y-auto p-4 bg-slate-900 rounded-lg focus:outline-none" 
                     contenteditable="true"
                     oninput="updateWordCount()"
                     onkeyup="updateWordCount()">
                    <?php if ($document && $document['content']): ?>
                        <?php echo $document['content']; ?>
                    <?php else: 
                        $config = getEditorConfig($document['document_type'] ?? 'technical');
                    ?>
                        <h1 class="text-2xl font-bold mb-4">عنوان المستند</h1>
                        
                        <?php foreach ($config['sections'] as $section): ?>
                        <h2 class="text-xl font-bold mb-3 mt-6"><?php echo $section; ?></h2>
                        <p class="mb-4 text-gray-300">اكتب محتوى هذا القسم هنا...</p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- المعاينة (مخفية افتراضياً) -->
                <div id="preview" class="preview-content min-h-[500px] max-h-[600px] overflow-y-auto p-4 bg-slate-900 rounded-lg hidden">
                    <!-- المحتوى المعروض هنا -->
                </div>
            </div>
            
            <!-- شريط الحالة -->
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-700">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full ml-2"></span>
                        <span class="text-sm text-gray-400">آخر حفظ: <?php echo $document ? timeAgo($document['updated_at'] ?? '') : 'لم يتم الحفظ بعد'; ?></span>
                    </div>
                    
                    <?php if ($edit_mode && !empty($document['versions'])): ?>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <select id="version-selector" onchange="loadVersion(this.value)" class="bg-slate-700 border border-slate-600 rounded-lg text-sm px-3 py-1">
                            <?php foreach ($document['versions'] as $ver): ?>
                            <option value="<?php echo $ver['version_number']; ?>">
                                v<?php echo $ver['version_number']; ?> (<?php echo timeAgo($ver['created_at']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="text-sm text-gray-400" id="char-count">0 حرف</span>
                </div>
            </div>
        </div>
        
        <!-- بطاقة المرفقات -->
        <div class="cyber-border bg-slate-800 rounded-xl p-6 mt-6">
            <h3 class="text-lg font-bold mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                المرفقات
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- رفع ملفات -->
                <div>
                    <div class="border-2 border-dashed border-slate-600 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer"
                         onclick="document.getElementById('file-upload').click()">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm text-gray-400 mb-2">اسحب وأفلت الملفات هنا</p>
                        <input type="file" id="file-upload" multiple class="hidden" onchange="handleFileUpload(this.files)">
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm">
                            اختيار ملفات
                        </button>
                    </div>
                    
                    <div id="uploaded-files" class="mt-4 space-y-2">
                        <!-- الملفات المرفوعة تظهر هنا -->
                        <?php if ($edit_mode && $document['file_path']): ?>
                        <div class="flex items-center justify-between p-2 bg-slate-700 rounded-lg">
                            <button onclick="removeFile('<?php echo $document['file_path']; ?>')" class="text-red-400 hover:text-red-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            <span class="text-sm flex-1 mr-2"><?php echo basename($document['file_path']); ?></span>
                            <span class="text-xs text-gray-400"><?php echo formatFileSize($document['file_size']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- روابط خارجية -->
                <div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <input type="text" id="link-url" placeholder="https://..." 
                                   class="flex-1 px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                            <input type="text" id="link-title" placeholder="عنوان الرابط" 
                                   class="w-1/3 px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                        </div>
                        <button onclick="addLink()" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 rounded-lg">
                            إضافة رابط
                        </button>
                        
                        <div id="links-list" class="space-y-2 mt-4">
                            <!-- الروابط المضافة تظهر هنا -->
                            <?php if ($edit_mode && $document['links']): ?>
                                <!-- عرض الروابط المخزنة -->
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- نافذة إدراج رابط -->
<!-- ============================================= -->
<div id="link-modal" class="fixed inset-0 z-50 hidden items-center justify-center modal-backdrop">
    <div class="cyber-border bg-slate-800 rounded-xl p-8 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <button onclick="closeLinkModal()" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <h3 class="text-2xl font-bold text-right text-blue-400">إدراج رابط</h3>
        </div>
        
        <form id="link-form" onsubmit="insertLinkFromModal(event)">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">نص الرابط</label>
                    <input type="text" id="modal-link-text" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2 text-right">الرابط</label>
                    <input type="url" id="modal-link-url" required 
                           class="w-full px-4 py-3 bg-slate-700 border border-slate-600 rounded-lg focus:outline-none focus:border-blue-500 text-right"
                           placeholder="https://...">
                </div>
            </div>
            
            <div class="flex items-center space-x-4 space-x-reverse mt-6">
                <button type="button" onclick="closeLinkModal()" class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                    إلغاء
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-all cyber-glow">
                    إدراج
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript الخاص بالصفحة -->
<!-- ============================================= -->
<script>
// =============================================
// المتغيرات العامة
// =============================================
let currentDocumentId = '<?php echo $document['id'] ?? ''; ?>';
let tags = new Set();
let uploadedFiles = [];

// =============================================
// دوال المحرر
// =============================================

// حفظ المستند
function saveDocument() {
    const content = document.getElementById('editor').innerHTML;
    const title = document.getElementById('doc-title').value;
    
    if (!title.trim()) {
        showNotification('❌ الرجاء إدخال عنوان المستند', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', currentDocumentId ? 'edit_document' : 'add_document');
    formData.append('document_id', currentDocumentId);
    formData.append('title', title);
    formData.append('project_id', document.getElementById('doc-project').value);
    formData.append('document_type', document.getElementById('doc-type').value);
    formData.append('format', document.getElementById('doc-format').value);
    formData.append('version', document.getElementById('doc-version').value);
    formData.append('status', document.getElementById('doc-status').value);
    formData.append('content', content);
    formData.append('tags', Array.from(tags).join(','));
    
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
            if (!currentDocumentId && data.document_id) {
                currentDocumentId = data.document_id;
            }
            setTimeout(() => {
                window.location.href = '?page=documents&view=' + currentDocumentId;
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

// معاينة المستند
function previewDocument() {
    const editor = document.getElementById('editor');
    const preview = document.getElementById('preview');
    
    if (preview.classList.contains('hidden')) {
        // إظهار المعاينة
        preview.innerHTML = editor.innerHTML;
        preview.classList.remove('hidden');
        editor.classList.add('hidden');
    } else {
        // إخفاء المعاينة
        preview.classList.add('hidden');
        editor.classList.remove('hidden');
    }
}

// تنسيق النص
function formatText(format) {
    document.execCommand(format, false, null);
    document.getElementById('editor').focus();
}

// إدراج عنوان
function insertHeading(level) {
    document.execCommand('formatBlock', false, 'h' + level);
    document.getElementById('editor').focus();
}

// إدراج قائمة
function insertList(type) {
    document.execCommand('insert' + (type === 'ul' ? 'Unordered' : 'Ordered') + 'List', false, null);
    document.getElementById('editor').focus();
}

// إدراج كود
function insertCode() {
    const selection = window.getSelection();
    const range = selection.getRangeAt(0);
    const pre = document.createElement('pre');
    const code = document.createElement('code');
    code.className = 'bg-slate-800 p-2 rounded block';
    code.textContent = '// اكتب الكود هنا';
    pre.appendChild(code);
    range.insertNode(pre);
}

// إدراج رابط
function insertLink() {
    document.getElementById('link-modal').classList.remove('hidden');
}

function closeLinkModal() {
    document.getElementById('link-modal').classList.add('hidden');
    document.getElementById('link-form').reset();
}

function insertLinkFromModal(event) {
    event.preventDefault();
    
    const text = document.getElementById('modal-link-text').value;
    const url = document.getElementById('modal-link-url').value;
    
    const link = `<a href="${url}" target="_blank" class="text-blue-400 hover:underline">${text}</a>`;
    document.execCommand('insertHTML', false, link);
    
    closeLinkModal();
    document.getElementById('editor').focus();
}

// إدراج صورة
function insertImage() {
    const url = prompt('أدخل رابط الصورة:');
    if (url) {
        document.execCommand('insertImage', false, url);
    }
}

// تحديث عدد الكلمات
function updateWordCount() {
    const editor = document.getElementById('editor');
    const text = editor.innerText || editor.textContent;
    const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
    const chars = text.length;
    
    document.getElementById('word-count').textContent = words + ' كلمة';
    document.getElementById('char-count').textContent = chars + ' حرف';
}

// ملء الشاشة
function toggleFullscreen() {
    const container = document.getElementById('editor-container');
    if (!document.fullscreenElement) {
        container.requestFullscreen();
    } else {
        document.exitFullscreen();
    }
}

// =============================================
// دوال القوالب
// =============================================
function loadTemplate(templateId) {
    if (confirm('تحميل هذا القالب سيفقد التغييرات غير المحفوظة. هل تريد المتابعة؟')) {
        window.location.href = '?page=creation&template=' + templateId;
    }
}

function loadVersion(version) {
    // محاكاة تحميل إصدار سابق
    showNotification('📖 جاري تحميل الإصدار ' + version, 'info');
}

// =============================================
// دوال الوسوم
// =============================================
function addTag() {
    const input = document.getElementById('tag-input');
    const tag = input.value.trim();
    
    if (tag && !tags.has(tag)) {
        tags.add(tag);
        renderTags();
        input.value = '';
    }
}

function addTagByName(tag) {
    if (!tags.has(tag)) {
        tags.add(tag);
        renderTags();
    }
}

function removeTag(tag) {
    tags.delete(tag);
    renderTags();
}

function renderTags() {
    const container = document.getElementById('tags-container');
    container.innerHTML = '';
    
    tags.forEach(tag => {
        const span = document.createElement('span');
        span.className = 'px-3 py-1 bg-blue-600 bg-opacity-20 text-blue-400 rounded-full text-sm flex items-center';
        span.innerHTML = `
            #${tag}
            <button onclick="removeTag('${tag}')" class="mr-2 text-gray-400 hover:text-red-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        container.appendChild(span);
    });
}

// =============================================
// دوال المرفقات
// =============================================
function handleFileUpload(files) {
    const container = document.getElementById('uploaded-files');
    
    Array.from(files).forEach(file => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-2 bg-slate-700 rounded-lg';
            div.innerHTML = `
                <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
                <span class="text-sm flex-1 mr-2">${file.name}</span>
                <span class="text-xs text-gray-400">${(file.size / 1024).toFixed(1)} KB</span>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    
    showNotification(`✅ تم رفع ${files.length} ملف`, 'success');
}

function addLink() {
    const url = document.getElementById('link-url').value;
    const title = document.getElementById('link-title').value;
    
    if (!url || !title) {
        showNotification('❌ الرجاء إدخال الرابط والعنوان', 'error');
        return;
    }
    
    const container = document.getElementById('links-list');
    const div = document.createElement('div');
    div.className = 'flex items-center justify-between p-2 bg-slate-700 rounded-lg';
    div.innerHTML = `
        <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
        <a href="${url}" target="_blank" class="text-sm flex-1 mr-2 text-blue-400 hover:underline">${title}</a>
    `;
    container.appendChild(div);
    
    document.getElementById('link-url').value = '';
    document.getElementById('link-title').value = '';
    showNotification('✅ تم إضافة الرابط', 'success');
}

function removeFile(filePath) {
    if (confirm('هل أنت متأكد من حذف هذا الملف؟')) {
        // حذف الملف
        showNotification('✅ تم حذف الملف', 'success');
    }
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

// =============================================
// تهيئة الصفحة
// =============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ صفحة إنشاء التوثيق جاهزة');
    
    // تحميل الوسوم إذا وجدت
    <?php if ($document && $document['tags']): ?>
    <?php foreach (explode(',', $document['tags']) as $tag): ?>
    tags.add('<?php echo trim($tag); ?>');
    <?php endforeach; ?>
    renderTags();
    <?php endif; ?>
    
    // تحديث عدد الكلمات
    updateWordCount();
    
    // اختصارات لوحة المفاتيح
    document.addEventListener('keydown', function(e) {
        // Ctrl+S = حفظ
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveDocument();
        }
        
        // Ctrl+P = معاينة
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            previewDocument();
        }
        
        // Ctrl+B = عريض
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            formatText('bold');
        }
        
        // Ctrl+I = مائل
        if (e.ctrlKey && e.key === 'i') {
            e.preventDefault();
            formatText('italic');
        }
    });
});
</script>

<!-- ============================================= -->
<!-- CSS إضافي للصفحة -->
<!-- ============================================= -->
<style>
.editor-content:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.editor-content h1 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.editor-content h2 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 1.5rem 0 0.75rem;
}

.editor-content h3 {
    font-size: 1.25rem;
    font-weight: bold;
    margin: 1.25rem 0 0.5rem;
}

.editor-content p {
    margin-bottom: 1rem;
    line-height: 1.6;
}

.editor-content ul, .editor-content ol {
    margin: 1rem 0;
    padding-right: 2rem;
}

.editor-content li {
    margin-bottom: 0.25rem;
}

.editor-content pre {
    background: #0f172a;
    padding: 1rem;
    border-radius: 0.5rem;
    margin: 1rem 0;
    overflow-x: auto;
}

.editor-content code {
    font-family: monospace;
    color: #e2e8f0;
}

.editor-content a {
    color: #3b82f6;
    text-decoration: underline;
}

.editor-content a:hover {
    color: #60a5fa;
}

.editor-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1rem 0;
}

.template-item {
    transition: all 0.2s ease;
}

.template-item:hover {
    transform: translateX(-5px);
}

.scrollbar-custom::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
    background: #1e293b;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
    background: #3b82f6;
    border-radius: 3px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
    background: #2563eb;
}

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

/* تنسيقات للمحرر في وضع ملء الشاشة */
#editor-container:fullscreen {
    background: #0f172a;
    padding: 2rem;
}

#editor-container:fullscreen .editor-content {
    max-width: 800px;
    margin: 0 auto;
}
</style>