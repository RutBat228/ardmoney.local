$(document).ready(function() {
    $("#mail").keyup(function() {
        var name = $('#mail').val();
        
        if (name === "") {
            $("#display").html("");
        } else {
            $.ajax({
                type: "POST",
                url: "email_search.php", 
                data: {
                    search: name // Значение для поиска
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
    $('#mail').val(Value);
    $('#display').hide();
}