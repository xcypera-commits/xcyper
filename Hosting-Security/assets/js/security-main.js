/**
 * الملف الرئيسي للأمن - يتضمن جميع الأدوات الأمنية
 */

// تهيئة جميع أنظمة الأمن
document.addEventListener('DOMContentLoaded', function() {
    // 1. تهيئة إدارة الجلسات
    SessionManager.init();
    
    // 2. إعداد التحقق من المدخلات
    InputValidation.setupAllForms();
    
    // 3. حماية من فتح نوافذ متعددة
    SecurityUtils.preventMultipleTabs();
    
    // 4. مراقبة الخمول
    SecurityUtils.setupInactivityLogout();
    
    // 5. تطبيق سياسات الأمان
    applySecurityPolicies();
});

/**
 * تطبيق سياسات الأمان على جميع النماذج
 */
function applySecurityPolicies() {
    // منع نسخ ولصق في حقول كلمات المرور
    document.querySelectorAll('input[type="password"]').forEach(input => {
        input.addEventListener('copy', (e) => e.preventDefault());
        input.addEventListener('paste', (e) => e.preventDefault());
    });
    
    // منع النقر الأيمن على العناصر الحساسة
    document.querySelectorAll('.sensitive-data').forEach(element => {
        element.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            alert('هذا الإجراء غير مسموح به');
        });
    });
    
    // تشفير البيانات الحساسة قبل الإرسال
    document.querySelectorAll('form[data-encrypt]').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sensitiveFields = this.querySelectorAll('[data-sensitive]');
            
            for (let field of sensitiveFields) {
                if (field.value) {
                    const encrypted = await ClientEncryption.encryptSensitiveData(field.value);
                    formData.set(field.name, encrypted);
                }
            }
            
            // إرسال البيانات المشفرة
            const response = await fetch(this.action, {
                method: this.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            handleFormResponse(result, this);
        });
    });
}

/**
 * معالجة استجابات النماذج
 */
function handleFormResponse(result, form) {
    if (result.success) {
        if (result.redirect) {
            window.location.href = result.redirect;
        } else if (result.message) {
            showNotification(result.message, 'success');
            form.reset();
        }
    } else {
        showNotification(result.message || 'حدث خطأ', 'error');
        
        // إظهار الأخطاء المحددة
        if (result.errors) {
            for (const [field, error] of Object.entries(result.errors)) {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) {
                    InputValidation.showError(input, error);
                }
            }
        }
    }
}

/**
 * إظهار إشعارات للمستخدم
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

/**
 * تسجيل أحداث الأمان
 */
function logSecurityEvent(eventType, details) {
    const event = {
        type: eventType,
        details: details,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        url: window.location.href
    };
    
    // إرسال الحدث إلى السيرفر
    fetch('/api/security/monitoring.php?action=log_event', {
        method: 'POST',
        body: JSON.stringify(event),
        headers: {
            'Content-Type': 'application/json'
        }
    });
}

// تصدير الدوال للاستخدام العالمي
window.Security = {
    utils: SecurityUtils,
    validation: InputValidation,
    session: SessionManager,
    encryption: ClientEncryption,
    logEvent: logSecurityEvent
};