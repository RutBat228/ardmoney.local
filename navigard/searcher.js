$(document).ready(function() {
    $("#search").keyup(function() {
        var name = $('#search').val();
        
        if (name === "") {
            $("#display").html("");
        } else {
            $.ajax({
                type: "POST",
                url: "lifesearch.php",
                data: {
                    search: name // Значение для поиска в таблице navigard_adress
                },
                success: function(response) {
                    $("#display").html(response).show(); // Показываем результаты поиска
                }
            });
        }
    });
});

// Заполняет поле поиска выбранным значением, но не скрывает результаты
function fill(Value) {
    $('#search').val(Value);
    // Мы больше не скрываем результаты, чтобы пользователь мог выбрать другой вариант
    // $('#display').hide();
}