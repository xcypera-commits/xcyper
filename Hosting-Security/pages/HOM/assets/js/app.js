// assets/js/dashboard.js

// ============================================
// تهيئة الصفحة
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardStats();
    loadServices();
    loadCategories();
    loadRequests();
    initEventListeners();
});

// ============================================
// تحميل الإحصائيات
// ============================================
function loadDashboardStats() {
    fetch('api/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsUI(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateStatsUI(stats) {
    document.getElementById('total-services').textContent = stats.total_services;
    document.getElementById('total-requests').textContent = stats.total_requests;
    document.getElementById('total-categories').textContent = stats.total_categories;
    document.getElementById('total-active').textContent = stats.active_services;
    
    // تحديث توزيع الفئات
    renderCategoryChart(stats.category_distribution);
    
    // تحديث آخر الطلبات
    renderRecentRequests(stats.recent_requests);
    
    // تحديث الخدمات الأكثر طلباً
    renderPopularServices(stats.popular_services);
}

// ============================================
// تحميل الخدمات
// ============================================
function loadServices() {
    fetch('api/services.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderServicesTable(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function renderServicesTable(services) {
    const tbody = document.getElementById('services-table-body');
    
    if (!services || services.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 py-8">لا توجد خدمات</td></tr>';
        return;
    }
    
    tbody.innerHTML = services.map((service, index) => {
        const features = JSON.parse(service.features || '[]');
        
        return `
            <tr>
                <td>${index + 1}</td>
                <td>
                    <div class="font-bold">${service.name}</div>
                    <div class="text-sm text-gray-400">${service.description.substring(0, 30)}...</div>
                </td>
                <td>
                    <span class="badge badge-${service.category_color || 'blue'}">${service.category_name}</span>
                </td>
                <td>${service.price}</td>
                <td>
                    <span class="badge ${service.status === 'active' ? 'badge-green' : 'badge-red'}">
                        ${service.status === 'active' ? 'نشط' : 'غير نشط'}
                    </span>
                </td>
                <td>
                    <div class="flex flex-wrap gap-1">
                        ${features.slice(0, 2).map(f => `
                            <span class="badge badge-blue text-xs">${f}</span>
                        `).join('')}
                        ${features.length > 2 ? `<span class="badge badge-blue text-xs">+${features.length - 2}</span>` : ''}
                    </div>
                </td>
                <td>
                    <div class="flex gap-2">
                        <button onclick="editService(${service.id})" class="action-btn edit-btn" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="viewService(${service.id})" class="action-btn view-btn" title="عرض">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="deleteService(${service.id})" class="action-btn delete-btn" title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ============================================
// تحميل الفئات
// ============================================
function loadCategories() {
    fetch('api/categories.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCategoriesTable(data.data);
                updateCategorySelect(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function renderCategoriesTable(categories) {
    const tbody = document.getElementById('categories-table-body');
    
    tbody.innerHTML = categories.map((cat, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${cat.name}</td>
            <td><span class="badge badge-${cat.color}">${cat.category_key}</span></td>
            <td>${cat.services_count || 0}</td>
            <td>
                <div class="flex gap-2">
                    <button onclick="editCategory(${cat.id})" class="action-btn edit-btn">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteCategory(${cat.id})" class="action-btn delete-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updateCategorySelect(categories) {
    const select = document.getElementById('service-category');
    select.innerHTML = '<option value="">اختر الفئة</option>' + 
        categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
}

// ============================================
// تحميل الطلبات
// ============================================
function loadRequests() {
    fetch('api/requests.php?action=get_all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderRequestsTable(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

function renderRequestsTable(requests) {
    const tbody = document.getElementById('requests-table-body');
    
    tbody.innerHTML = requests.map((req, index) => {
        const statusClass = {
            'new': 'badge-green',
            'reviewing': 'badge-blue',
            'accepted': 'badge-purple',
            'rejected': 'badge-red',
            'completed': 'badge-green'
        };
        
        const statusText = {
            'new': 'جديد',
            'reviewing': 'قيد المراجعة',
            'accepted': 'مقبول',
            'rejected': 'مرفوض',
            'completed': 'مكتمل'
        };
        
        return `
            <tr>
                <td>${index + 1}</td>
                <td>${req.full_name}</td>
                <td>${req.email}</td>
                <td>${req.phone}</td>
                <td>${req.service_name || req.service_type}</td>
                <td>${new Date(req.created_at).toLocaleDateString('ar-EG')}</td>
                <td>
                    <span class="badge ${statusClass[req.status]}">${statusText[req.status]}</span>
                </td>
                <td>
                    <div class="flex gap-2">
                        <button onclick="viewRequest(${req.id})" class="action-btn view-btn">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="updateRequestStatus(${req.id})" class="action-btn edit-btn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// ============================================
// دوال إضافة/تعديل الخدمات
// ============================================
function openAddModal() {
    document.getElementById('modal-title').textContent = 'إضافة خدمة جديدة';
    document.getElementById('service-id').value = '';
    document.getElementById('service-form').reset();
    document.getElementById('service-modal').classList.remove('hidden');
    document.getElementById('service-modal').classList.add('flex');
}

function editService(id) {
    fetch(`api/services.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const service = data.data;
                
                document.getElementById('modal-title').textContent = 'تعديل الخدمة';
                document.getElementById('service-id').value = service.id;
                document.getElementById('service-name').value = service.name;
                document.getElementById('service-category').value = service.category_id;
                document.getElementById('service-description').value = service.description;
                document.getElementById('service-price').value = service.price;
                document.getElementById('service-time').value = service.setup_time || '';
                document.getElementById('service-features').value = service.features.join(', ');
                document.getElementById('service-sla').value = service.sla || '';
                document.getElementById('service-status').checked = service.status === 'active';
                document.getElementById('service-popular').checked = service.popular == 1;
                
                document.getElementById('service-modal').classList.remove('hidden');
                document.getElementById('service-modal').classList.add('flex');
            }
        })
        .catch(error => console.error('Error:', error));
}

function saveService(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', document.getElementById('service-id').value ? 'update' : 'add');
    formData.append('id', document.getElementById('service-id').value);
    formData.append('name', document.getElementById('service-name').value);
    formData.append('category_id', document.getElementById('service-category').value);
    formData.append('description', document.getElementById('service-description').value);
    formData.append('price', document.getElementById('service-price').value);
    formData.append('setup_time', document.getElementById('service-time').value);
    formData.append('features', document.getElementById('service-features').value);
    formData.append('sla', document.getElementById('service-sla').value);
    formData.append('status', document.getElementById('service-status').checked ? 'active' : 'inactive');
    formData.append('popular', document.getElementById('service-popular').checked ? '1' : '0');
    
    fetch('api/services.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            loadServices();
            loadDashboardStats();
            alert(data.message);
        } else {
            alert('خطأ: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال');
    });
}

function deleteService(id) {
    if (confirm('هل أنت متأكد من حذف هذه الخدمة؟')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('api/services.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadServices();
                loadDashboardStats();
                alert(data.message);
            } else {
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال');
        });
    }
}

// ============================================
// دوال إضافية
// ============================================
function closeModal() {
    document.getElementById('service-modal').classList.add('hidden');
    document.getElementById('service-modal').classList.remove('flex');
}

function filterServices() {
    // سيتم إضافة فلترة لاحقاً
}

function logout() {
    window.location.href = 'logout.php';
}