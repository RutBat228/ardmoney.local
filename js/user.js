$(document).ready(function() {
    // Обработчик клика по блоку зарплаты
    $(document).on('click', '.salary-block', function() {
        const userId = $(this).data('user-id');
        const month = $(this).data('month');
        const year = $(this).data('year');
        
        // Открываем модальное окно редактирования дежурств
        $('#editDutiesModal').modal('show');
        
        // Загружаем существующие дежурства
        $.ajax({
            url: 'get_duties.php',
            method: 'POST',
            data: {
                user_id: userId,
                month: month,
                year: year
            },
            success: function(response) {
                if (response.success) {
                    // Очищаем календарь
                    $('#dutyCalendar').fullCalendar('removeEvents');
                    
                    // Добавляем существующие дежурства
                    response.duties.forEach(function(duty) {
                        $('#dutyCalendar').fullCalendar('renderEvent', {
                            title: 'Дежурство',
                            start: duty.date,
                            allDay: true,
                            className: 'duty-event'
                        });
                    });
                }
            }
        });
        
        // Загружаем праздничные дни
        $.ajax({
            url: 'get_holidays.php',
            method: 'POST',
            data: {
                month: month,
                year: year
            },
            success: function(response) {
                if (response.success) {
                    // Обновляем праздничные дни в календаре
                    response.holidays.forEach(function(holiday) {
                        $('#dutyCalendar').fullCalendar('renderEvent', {
                            title: 'Праздник',
                            start: holiday.date,
                            allDay: true,
                            className: 'holiday-event'
                        });
                    });
                }
            }
        });
    });
}); 