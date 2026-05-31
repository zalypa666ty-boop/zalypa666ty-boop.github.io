/**
 * Основной JavaScript для сайта страховой компании
 * Асинхронные запросы, валидация, калькуляторы
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== Burger-меню для мобильных устройств =====
    const burger = document.querySelector('.burger');
    const nav = document.querySelector('.nav');
    
    if (burger) {
        burger.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // ===== Валидация форм на лету =====
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                    showError(field, 'Это поле обязательно');
                } else {
                    field.classList.remove('error');
                    hideError(field);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Пожалуйста, заполните все обязательные поля', 'error');
            }
        });
    });
    
    // ===== Калькулятор страховки (асинхронный) =====
    const calculatorForm = document.getElementById('calculator-form');
    if (calculatorForm) {
        calculatorForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const type = document.getElementById('insurance-type').value;
            const formData = new FormData(calculatorForm);
            formData.append('action', 'calculate');
            formData.append('type', type);
            
            showLoading(true);
            
            try {
                const response = await fetch('/polygon-insurance/api?action=calculate', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('result-price').innerHTML = data.premium + ' ₽';
                    document.getElementById('result-block').style.display = 'block';
                    
                    // Сохраняем данные для оформления полиса
                    window.calculatedData = data;
                } else {
                    showNotification(data.error || 'Ошибка расчета', 'error');
                }
            } catch (error) {
                showNotification('Ошибка соединения с сервером', 'error');
            } finally {
                showLoading(false);
            }
        });
    }
    
    // ===== Оформление полиса =====
    const applyButton = document.getElementById('apply-policy');
    if (applyButton) {
        applyButton.addEventListener('click', async function() {
            if (!window.calculatedData) {
                showNotification('Сначала выполните расчет', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('type', window.calculatedData.type);
            formData.append('premium', window.calculatedData.premium);
            formData.append('data', JSON.stringify(window.calculatedData.data));
            
            try {
                const response = await fetch('/polygon-insurance/api?action=create_policy', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Полис успешно оформлен! Номер: ' + data.policy_number, 'success');
                    setTimeout(() => {
                        window.location.href = '/polygon-insurance/client';
                    }, 2000);
                } else {
                    showNotification(data.error || 'Ошибка оформления', 'error');
                }
            } catch (error) {
                showNotification('Ошибка соединения с сервером', 'error');
            }
        });
    }
    
    // ===== Поиск и фильтрация полисов =====
    const searchInput = document.getElementById('search-policies');
    const filterSelect = document.getElementById('filter-status');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterPolicies();
        });
    }
    
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterPolicies();
        });
    }
    
    function filterPolicies() {
        const searchText = searchInput ? searchInput.value.toLowerCase() : '';
        const filterStatus = filterSelect ? filterSelect.value : '';
        const rows = document.querySelectorAll('.policy-row');
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const status = row.dataset.status;
            let show = true;
            
            if (searchText && !text.includes(searchText)) {
                show = false;
            }
            
            if (filterStatus && filterStatus !== 'all' && status !== filterStatus) {
                show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }
    
    // ===== Генерация PDF =====
    const pdfButtons = document.querySelectorAll('.pdf-download');
    pdfButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const policyId = this.dataset.policyId;
            
            try {
                const response = await fetch(`/polygon-insurance/api?action=generate_pdf&policy_id=${policyId}`);
                const data = await response.json();
                
                if (data.success && data.url) {
                    window.open(data.url, '_blank');
                } else {
                    showNotification('Ошибка генерации PDF', 'error');
                }
            } catch (error) {
                showNotification('Ошибка соединения с сервером', 'error');
            }
        });
    });
    
    // ===== Управление пользователями (админ) =====
    const userStatusToggles = document.querySelectorAll('.toggle-status');
    userStatusToggles.forEach(toggle => {
        toggle.addEventListener('click', async function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            const currentStatus = this.dataset.currentStatus;
            const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
            
            if (confirm(`Вы уверены, что хотите ${newStatus === 'blocked' ? 'заблокировать' : 'разблокировать'} пользователя?`)) {
                try {
                    const response = await fetch('/polygon-insurance/api?action=toggle_user_status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ user_id: userId, status: newStatus })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        showNotification(data.error || 'Ошибка', 'error');
                    }
                } catch (error) {
                    showNotification('Ошибка соединения с сервером', 'error');
                }
            }
        });
    });
    
    // ===== Вспомогательные функции =====
    function showLoading(show) {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.textContent = message;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '300px';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    function showError(field, message) {
        let error = field.parentNode.querySelector('.error-message');
        if (!error) {
            error = document.createElement('small');
            error.className = 'error-message';
            error.style.color = '#dc3545';
            field.parentNode.appendChild(error);
        }
        error.textContent = message;
    }
    
    function hideError(field) {
        const error = field.parentNode.querySelector('.error-message');
        if (error) {
            error.remove();
        }
    }
    
    // ===== Адаптация калькулятора =====
    const insuranceTypeSelect = document.getElementById('insurance-type');
    if (insuranceTypeSelect) {
        insuranceTypeSelect.addEventListener('change', function() {
            const type = this.value;
            const osagoFields = document.getElementById('osago-fields');
            const cascoFields = document.getElementById('casco-fields');
            const healthFields = document.getElementById('health-fields');
            
            osagoFields.style.display = 'none';
            cascoFields.style.display = 'none';
            healthFields.style.display = 'none';
            
            if (type === 'osago') osagoFields.style.display = 'block';
            else if (type === 'casco') cascoFields.style.display = 'block';
            else if (type === 'health') healthFields.style.display = 'block';
        });
    }
});