// js/request.js

// =====================================================
// نظام طلبات الخدمة - X Cyber Hosting
// النسخة النهائية مع تحسين التحقق من رقم الهاتف
// =====================================================

const API_BASE_URL = 'http://localhost/xcyber-hosting/api';

// =====================================================
// التحقق من حالة تسجيل الدخول عند تحميل الصفحة
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    checkLoginStatus();
    populateUserData();
});

// =====================================================
// التحقق من تسجيل الدخول وتعطيل النموذج إذا لزم الأمر
// =====================================================
function checkLoginStatus() {
    const isLoggedIn = sessionStorage.getItem('visitor_logged_in') === 'true';
    const requestSection = document.getElementById('request');
    const form = document.getElementById('serviceRequestForm');
    
    if (requestSection && form) {
        if (!isLoggedIn) {
            // إزالة أي رسالة سابقة
            const oldMessage = document.getElementById('loginWarningMessage');
            if (oldMessage) oldMessage.remove();
            
            // إضافة رسالة فوق النموذج
            const messageDiv = document.createElement('div');
            messageDiv.id = 'loginWarningMessage';
            messageDiv.className = 'text-center p-4 bg-yellow-600/20 border border-yellow-600 rounded-lg mb-4';
            messageDiv.innerHTML = `
                <p class="text-yellow-400">
                    <i class="fas fa-exclamation-triangle ml-2"></i>
                    يرجى <a href="login.html" class="font-bold underline hover:text-yellow-300">تسجيل الدخول</a> أولاً لإرسال طلب الخدمة
                </p>
            `;
            
            form.parentNode.insertBefore(messageDiv, form);
            
            // تعطيل النموذج
            form.querySelectorAll('input, select, textarea, button').forEach(el => {
                el.disabled = true;
            });
        } else {
            // إزالة رسالة التحذير إذا كانت موجودة
            const oldMessage = document.getElementById('loginWarningMessage');
            if (oldMessage) oldMessage.remove();
            
            // تمكين النموذج
            form.querySelectorAll('input, select, textarea, button').forEach(el => {
                el.disabled = false;
            });
        }
    }
}

// =====================================================
// تعبئة بيانات المستخدم تلقائياً في النموذج
// =====================================================
function populateUserData() {
    const isLoggedIn = sessionStorage.getItem('visitor_logged_in') === 'true';
    
    if (isLoggedIn) {
        const visitorName = sessionStorage.getItem('visitor_name');
        const visitorEmail = sessionStorage.getItem('visitor_email');
        
        if (visitorName) {
            const nameField = document.getElementById('fullName');
            if (nameField) nameField.value = visitorName;
        }
        
        if (visitorEmail) {
            const emailField = document.getElementById('email');
            if (emailField) emailField.value = visitorEmail;
        }
    }
}

// =====================================================
// معالجة نموذج الطلب
// =====================================================
document.getElementById('serviceRequestForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // التحقق من تسجيل الدخول أولاً
    const isLoggedIn = sessionStorage.getItem('visitor_logged_in') === 'true';
    
    if (!isLoggedIn) {
        showMessage('❌ يجب تسجيل الدخول أولاً لإرسال طلب الخدمة', 'error');
        
        // تحويل لصفحة تسجيل الدخول بعد ثانيتين
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
        
        return;
    }
    
    // جمع البيانات من النموذج
    const formData = {
        service_type: document.getElementById('serviceType').value,
        service_name: document.getElementById('serviceType').selectedOptions[0].text,
        full_name: document.getElementById('fullName').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        company_name: document.getElementById('companyName').value.trim() || null,
        details: document.getElementById('details').value.trim(),
        agreed_to_terms: document.getElementById('terms').checked,
        visitor_id: sessionStorage.getItem('visitor_id')
    };

    console.log('🔵 مستخدم مسجل الدخول:', sessionStorage.getItem('visitor_name'));
    console.log('📝 بيانات الطلب:', formData);
    
    // التحقق من صحة البيانات
    if (!validateForm(formData)) {
        return;
    }
    
    // تعطيل الزر أثناء الإرسال
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>جاري الإرسال...</span> <i class="fas fa-spinner fa-spin mr-2"></i>';
    
    hideMessage();
    
    try {
        const response = await fetch(`${API_BASE_URL}/service_requests.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('✅ تم إرسال طلبك بنجاح! سنتواصل معك قريباً', 'success');
            document.getElementById('serviceRequestForm').reset();
        } else {
            showMessage(`❌ ${result.message}`, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showMessage('❌ فشل الاتصال بالخادم', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// =====================================================
// التحقق من صحة البيانات - نسخة محسنة للهاتف
// =====================================================
function validateForm(data) {
    console.log('📝 البيانات المرسلة:', data);
    console.log('طول الاسم:', data.full_name.length);
    console.log('قيمة الاسم:', `"${data.full_name}"`);
    
    // التحقق من الاسم
    if (data.full_name.length < 3) {
        showMessage('❌ الاسم يجب أن يكون 3 أحرف على الأقل', 'error');
        return false;
    }
    
    // التحقق من البريد الإلكتروني
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
        showMessage('❌ البريد الإلكتروني غير صالح', 'error');
        return false;
    }
    
    // ===== تحسين التحقق من رقم الهاتف =====
    // إزالة المسافات والشرطات والأحرف غير الرقمية
    const cleanPhone = data.phone.replace(/[^0-9]/g, '');
    console.log('📞 الرقم بعد التنظيف:', cleanPhone);
    
    // التحقق من الطول (10 أرقام أو 7 أرقام)
    if (cleanPhone.length !== 10 && cleanPhone.length !== 7) {
        showMessage('❌ رقم الهاتف غير صالح (يجب أن يكون 7 أو 10 أرقام)', 'error');
        return false;
    }
    
    // التحقق من أن الرقم يبدأ بـ 05 أو 5
    if (!cleanPhone.startsWith('05') && !cleanPhone.startsWith('5')) {
        showMessage('❌ رقم الهاتف يجب أن يبدأ بـ 05', 'error');
        return false;
    }
    // =====================================
    
    // التحقق من نوع الخدمة
    if (!data.service_type) {
        showMessage('❌ اختر نوع الخدمة', 'error');
        return false;
    }
    
    // التحقق من تفاصيل الطلب
    if (data.details.length < 10) {
        showMessage('❌ التفاصيل قصيرة جداً', 'error');
        return false;
    }
    
    // التحقق من الموافقة على الشروط
    if (!data.agreed_to_terms) {
        showMessage('❌ يجب الموافقة على الشروط', 'error');
        return false;
    }
    
    return true;
}

// =====================================================
// عرض الرسائل
// =====================================================
function showMessage(text, type) {
    const messageDiv = document.getElementById('formMessage');
    messageDiv.className = `p-4 rounded-lg text-center ${type === 'success' ? 'bg-green-600/20 text-green-400 border border-green-600' : 'bg-red-600/20 text-red-400 border border-red-600'}`;
    messageDiv.textContent = text;
    messageDiv.classList.remove('hidden');
    
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideMessage() {
    const messageDiv = document.getElementById('formMessage');
    if (messageDiv) {
        messageDiv.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('serviceRequestForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitServiceRequest();
        });
    }
});

function submitServiceRequest() {
    const formData = new FormData();
    
    formData.append('full_name', document.getElementById('fullName').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('phone', document.getElementById('phone').value);
    formData.append('company', document.getElementById('companyName').value || '');
    formData.append('service_type', document.getElementById('serviceType').value);
    formData.append('details', document.getElementById('details').value);
    
    // إرسال الطلب إلى API
    fetch('api/requests.php?action=add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('formMessage');
        messageDiv.classList.remove('hidden');
        
        if (data.success) {
            messageDiv.className = 'p-4 rounded-lg text-center bg-green-500/20 border border-green-500/30 text-green-400';
            messageDiv.innerHTML = '<i class="fas fa-check-circle ml-2"></i> تم إرسال طلبك بنجاح! سنتواصل معك قريباً.';
            document.getElementById('serviceRequestForm').reset();
        } else {
            messageDiv.className = 'p-4 rounded-lg text-center bg-red-500/20 border border-red-500/30 text-red-400';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle ml-2"></i> حدث خطأ: ' + data.message;
        }
        
        // إخفاء الرسالة بعد 5 ثوان
        setTimeout(() => {
            messageDiv.classList.add('hidden');
        }, 5000);
    })
    .catch(error => {
        console.error('Error:', error);
        const messageDiv = document.getElementById('formMessage');
        messageDiv.classList.remove('hidden');
        messageDiv.className = 'p-4 rounded-lg text-center bg-red-500/20 border border-red-500/30 text-red-400';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle ml-2"></i> حدث خطأ في الاتصال بالخادم';
    });
}