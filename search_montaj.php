<?php
session_start();
include "inc/head.php";
AutorizeProtect();
access();
animate();

if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
    $month = date_view($_GET['date']);
    $date_current = $_GET['date'];
} else {
    $month = month_view(date('m'));
    $date = date("Y-m-d");
    $date_current = substr($date, 0, -3);
}
?>

<!DOCTYPE html>
<html lang="ru">
<body style="background: #ffffff url(img/background.webp) repeat;">
<div class="container-sm">
    
<main role="main" style="padding-bottom: 60px;">
        <div style="min-height: calc(100vh - 9rem); padding: 0 0; background: #fff;" class="jumbotron">
            <div class="col-md-12 col-sm-12 mx-auto">
                <title>Поиск монтажей - <?=$month?></title>

                <div class="input-group mt-4">
                    <span class="input-group-text">Поиск</span>
                    <input id="spterm" type="text" aria-label="адрес" class="form-control" oninput="searchMontaj()" placeholder="Введите адрес">
                </div>
                <div id="context" class="mt-4">
                    <div class="text-center pt-5">
                        <figure>
                            <blockquote class="blockquote">
                                <p class="display-5">🔍 Введите адрес в поисковую строку выше<br>чтобы найти историю монтажей</p>
                            </blockquote>
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Упрощенные анимации при загрузке страницы
    const inputGroup = document.querySelector(".input-group");
    const context = document.getElementById("context");

    inputGroup.style.opacity = "0";
    context.style.opacity = "0";

    setTimeout(() => {
        inputGroup.style.transition = "opacity 0.5s ease";
        inputGroup.style.opacity = "1";
    }, 100);

    setTimeout(() => {
        context.style.transition = "opacity 0.5s ease";
        context.style.opacity = "1";
    }, 200);
});

function searchMontaj() {
    let searchTerm = document.getElementById("spterm").value;

    if (searchTerm.length >= 2) {
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "obr_search_montaj.php?query=" + encodeURIComponent(searchTerm), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                document.getElementById("context").innerHTML = xhr.responseText;
                const results = document.querySelectorAll("#context .hui");
                results.forEach(result => {
                    result.style.opacity = "0";
                    result.style.transition = "opacity 0.3s ease";
                    setTimeout(() => result.style.opacity = "1", 50);
                });
            }
        };
        xhr.send();
    } else {
        document.getElementById("context").innerHTML = `
            <div class="text-center pt-5">
                <figure>
                    <blockquote class="blockquote">
                        <p class="display-5">🔍 Введите адрес в поисковую строку выше<br>чтобы найти историю монтажей</p>
                    </blockquote>
                </figure>
            </div>`;
        const context = document.getElementById("context");
        context.style.opacity = "0";
        context.style.transition = "opacity 0.5s ease";
        setTimeout(() => context.style.opacity = "1", 50);
    }
}
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html>