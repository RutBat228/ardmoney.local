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

// Заполняет поле поиска выбранным значением и скрывает результаты
function fill(Value) {
    $('#search').val(Value);
    $('#display').hide();
}