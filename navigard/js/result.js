$(document).ready(function() {
    initializeParticles();
    initializeEditMode();
});

function initializeParticles() {
    if (typeof particlesJS !== 'undefined') {
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 50,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#ffffff"
                },
                "opacity": {
                    "value": 0.2,
                    "random": true
                },
                "size": {
                    "value": 3,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#54a0ff",
                    "opacity": 0.2,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2,
                    "direction": "none",
                    "random": true,
                    "out_mode": "out"
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "grab"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    }
                }
            }
        });
    }
}

function initializeEditMode() {
    const editableFields = document.querySelector('.custom-editable-fields');
    const viewFields = document.querySelector('.custom-view-fields');
    const editMode = document.getElementById('editMode');
    const viewText = document.querySelector('.custom-toggle-text.custom-view');
    const editText = document.querySelector('.custom-toggle-text.custom-edit');
    
    if (editableFields && viewFields) {
        editableFields.style.display = 'none';
        viewFields.style.display = 'block';
        if (editMode) {
            editMode.checked = false;
        }
        if (viewText) {
            viewText.classList.add('active');
        }
        if (editText) {
            editText.classList.remove('active');
        }
    }
}

function toggleEditMode() {
    const editMode = document.getElementById('editMode').checked;
    const editableFields = document.querySelector('.custom-editable-fields');
    const viewFields = document.querySelector('.custom-view-fields');
    const slider = document.querySelector('.custom-toggle-slider');
    const viewText = document.querySelector('.custom-toggle-text.custom-view');
    const editText = document.querySelector('.custom-toggle-text.custom-edit');
    
    if (editMode) {
        editableFields.style.display = 'block';
        viewFields.style.display = 'none';
        slider.classList.add('edit-mode');
        editText.classList.add('active');
        viewText.classList.remove('active');
    } else {
        editableFields.style.display = 'none';
        viewFields.style.display = 'block';
        slider.classList.remove('edit-mode');
        viewText.classList.add('active');
        editText.classList.remove('active');
    }
}

function saveChanges(id) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'edit');
    formData.append('check', document.getElementById('completeSwitch').checked ? 1 : 0);
    
    const fields = ['adress', 'oboryda', 'pon', 'podjezd', 'krisha', 'kluch', 'lesnica', 'dopzamok', 'pitanie', 'link', 'region', 'pred', 'phone', 'text', 'vihod'];
    fields.forEach(field => {
        const element = document.getElementById(field);
        if (element) {
            if (field === 'vihod' && element.multiple) {
                const selected = Array.from(element.selectedOptions).map(option => option.value);
                selected.forEach((value, index) => formData.append(`vihod[${index}]`, value));
            } else {
                formData.append(field, element.value);
            }
        }
    });

    $.ajax({
        url: 'obr_result.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: handleSaveSuccess,
        error: handleSaveError
    });
}

function handleSaveSuccess(data) {
    showNotification(data.success, data.success ? 'Изменения успешно сохранены!' : 'Ошибка: ' + data.error);
    if (data.success) {
        updateViewFields(data.data);
        const textArea = document.getElementById('text');
        if (textArea) textArea.value = '';
        updateEditableNotes(data.data.text);
    }
}

function handleSaveError(xhr, status, error) {
    console.error('Ошибка:', error);
    console.error('Статус:', status);
    console.error('Ответ сервера:', xhr.responseText);
    
    let errorMessage = 'Произошла ошибка при сохранении изменений';
    
    try {
        // Пробуем распарсить JSON-ответ
        const response = JSON.parse(xhr.responseText);
        if (response && response.error) {
            errorMessage += ': ' + response.error;
        } else {
            errorMessage += ': ' + error;
        }
    } catch (e) {
        // Если не удалось распарсить JSON, выводим исходную ошибку
        errorMessage += ': ' + error;
    }
    
    showNotification(false, errorMessage);
}

function showNotification(isSuccess, message) {
    // Удаляем все существующие уведомления
    $('.notification-container').remove();
    
    const type = isSuccess ? 'success' : 'danger';
    const icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
    const backgroundColor = isSuccess ? '#28a745' : '#dc3545';
    
    // Создаем контейнер для уведомления
    const notification = $(`
        <div class="notification-container position-fixed top-50 start-50 translate-middle" style="z-index: 9999; max-width: 90%;">
            <div class="alert alert-${type} shadow-lg border-0 d-flex align-items-center p-4" style="min-width: 300px; background-color: ${backgroundColor}; color: white;">
                <div class="me-3">
                    <i class="fas ${icon} fa-2x"></i>
                </div>
                <div class="notification-message">
                    <strong class="d-block mb-1">${isSuccess ? 'Успешно!' : 'Внимание!'}</strong>
                    <span>${message}</span>
                </div>
            </div>
        </div>
    `);
    
    $('body').append(notification);
    
    // Анимация появления
    notification.css('opacity', '0');
    notification.css('transform', 'translate(-50%, -60%)');
    
    setTimeout(() => {
        notification.css('transition', 'all 0.3s ease');
        notification.css('opacity', '1');
        notification.css('transform', 'translate(-50%, -50%)');
    }, 10);
    
    // Исчезновение через некоторое время
    setTimeout(() => {
        notification.css('opacity', '0');
        notification.css('transform', 'translate(-50%, -40%)');
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

function startDelete(id, adress) {
    if (confirm("Точно удалить дом из базы?")) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('action', 'delete');
        formData.append('adress', adress);
        
        $.ajax({
            url: 'obr_result.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(true, response.message || 'Дом успешно удален');
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showNotification(false, response.error || 'Ошибка при удалении');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка:', error);
                console.error('Статус:', status);
                console.error('Ответ сервера:', xhr.responseText);
                
                let errorMessage = 'Произошла ошибка при удалении дома';
                
                try {
                    // Пробуем распарсить JSON-ответ
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage += ': ' + response.error;
                    } else {
                        errorMessage += ': ' + error;
                    }
                } catch (e) {
                    // Если не удалось распарсить JSON, выводим исходную ошибку
                    errorMessage += ': ' + error;
                }
                
                showNotification(false, errorMessage);
            }
        });
    }
} 