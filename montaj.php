<?php
session_start();
include("inc/function.php");
echo '<!doctype html><html lang="ru">';
include("inc/style.php"); // Подключаем стили из style.php
?>
<head>
    <title>Добавить работу</title>
    <script src="https://unpkg.com/@barba/core"></script>
    <style>
        body {
            background: linear-gradient(133deg, #122f18ed, #323331c2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .auth-container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 90px rgba(0, 0, 0, 0.5);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .auth-container img {
            width: 100%;
            margin-bottom: 1.5rem;
        }
        .auth-container h1 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .auth-container .btn-primary {
            background-color: #FFA726;
            border: none;
            padding: 0.6rem 1.2rem;
        }
        .auth-container .btn-secondary {
            background-color: #EF5350;
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
        }
        .auth-container a {
            display: block;
            margin-top: 1rem;
            color: #616161;
            text-decoration: none;
        }
        .auth-container a:hover {
            color: #000;
        }
        .pizdec {
            margin: auto;
            width: 95%;
            text-align: justify;
            padding: 0px 0px 0.1rem 1rem;
        }
        .montaj_input {
            padding: 1rem 0 0 0;
        }
        .montaj_textarea {
            width: 100%;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Varela+Round&display=swap'>
</head>
<body style="background: #ffffff url(img/background.webp) repeat;">
<div class="container-sm">
    <div data-barba="wrapper">
        <div data-barba="container" data-barba-namespace="montaj">
            
<main role="main" style = "padding-bottom: 60px;" >
                <div class="jumbotron" style="padding: 9% 0;">
                    <div style="display: grid;place-items: center;">
                        <?php
                        AutorizeProtect();
                        access();
                        global $connect;
                        global $usr;
                        ?>
                        <form method="GET" action="add_mon.php" style="font-family: system-ui;">
                            <div class="auth-container">
                                <a href="/index.php"><img src="img/logo.webp" alt="Логотип"></a>
                                <hr style="margin: -1rem 0;">
                                <div class="montaj_input">
                                    <input style="background: #3b46321f; border-radius: 0.5rem; border: 2px solid #36412fa6; margin: 1rem 0 0 0; color: #000;" 
                                        autofocus list="provlist" id="search" type="text" name="adress" class="form-control" required title="Введите от 4 символов" placeholder="Введите адрес">
                                    <div id="display"></div>
                                </div>
                                <script type="text/javascript" src="/js/searcher.js"></script>
                                <br>
                                <div class="mb-3">
                                    <textarea style="background: #3b46321f; border-radius: 0.5rem; border: 2px solid #36412fa6; margin: 1rem 0 0 0; color: #000;" 
                                        placeholder="Что там делал(кратко)" name="text" class="form-control montaj_textarea" id="exampleFormControlTextarea1" rows="3"></textarea>
                                </div>
                                <ul id="search-results" class="list-group" style="text-align: justify; width: 95%; margin: auto; padding: 0rem 0rem 0.5rem 0.5rem;"></ul>

                                <script>
                                    $(document).ready(function() {
                                        function search() {
                                            var searchTerm = $("#exampleFormControlTextarea1").val();
                                            $.ajax({
                                                url: "/search_4todelal.php",
                                                method: "POST",
                                                data: { search: searchTerm },
                                                success: function(data) {
                                                    $("#search-results").empty();
                                                    if (data.length > 0) {
                                                        var results = JSON.parse(data);
                                                        for (var i = 0; i < results.length; i++) {
                                                            $("#search-results").append("<li class='search-result list-group-item'>" + results[i].text + "</li>");
                                                        }
                                                        $(".search-result").click(function() {
                                                            var selectedText = $(this).text();
                                                            $("#exampleFormControlTextarea1").val(selectedText);
                                                            $("#search-results").empty();
                                                        });
                                                    }
                                                }
                                            });
                                        }
                                        $("#exampleFormControlTextarea1").on("input", function() {
                                            search();
                                        });
                                    });
                                </script>

                                <br>
                                <div class='form-text text-center fw-bold pb-4'>Кто был?</div>
                                <?php
                                $sql = "SELECT * FROM `user` WHERE `region` = '" . $usr['region'] . "' ORDER BY `brigada` ";
                                $res_data = mysqli_query($connect, $sql);
                                while ($tech = mysqli_fetch_array($res_data)) {
                                ?>
                                    <div class="form-check">
                                        <div id="checklist" class="form-check">
                                            <input type="checkbox" value="<?= $tech['fio'] ?>" name="technik[]" id="flexCheckDefault<?= $tech['id'] ?>">
                                            <label for="flexCheckDefault<?= $tech['id'] ?>"> <?= $tech['fio'] ?></label>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>
                                <input type="hidden" value="<?= $usr['region'] ?>" name="region">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-lg" style="background: #445e3b; border-radius: 1rem; border: 2px solid #2c3c26d1; margin: 3rem 0rem 1rem 0rem; color:#fff">Добавить монтаж</button>
                                </div>
                            </div>
                            <br>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимации GSAP только для montaj.php
    gsap.from(".auth-container", {
        opacity: 0,
        duration: 0.6,
        ease: "power2.out"
    });

    gsap.from("#search", {
        opacity: 0,
        y: 10,
        duration: 0.5,
        delay: 0.2,
        ease: "power2.out"
    });

    gsap.from("#exampleFormControlTextarea1", {
        opacity: 0,
        y: 10,
        duration: 0.5,
        delay: 0.3,
        ease: "power2.out"
    });

    gsap.from(".form-check", {
        opacity: 0,
        y: 10,
        duration: 0.5,
        delay: 0.4,
        ease: "power2.out"
    });

    gsap.from(".btn-lg", {
        opacity: 0,
        y: 10,
        duration: 0.5,
        delay: 0.5,
        ease: "power2.out"
    });

    // Анимация результатов поиска
    $("#exampleFormControlTextarea1").on("input", function() {
        setTimeout(() => {
            gsap.from(".search-result", {
                opacity: 0,
                y: 5,
                duration: 0.3,
                stagger: 0.05,
                ease: "power2.out"
            });
        }, 100);
    });

    // Повторная инициализация после перехода Barba.js
    barba.hooks.enter(() => {
        gsap.from(".auth-container", {
            opacity: 0,
            duration: 0.6,
            ease: "power2.out"
        });

        gsap.from("#search", {
            opacity: 0,
            y: 10,
            duration: 0.5,
            delay: 0.2,
            ease: "power2.out"
        });

        gsap.from("#exampleFormControlTextarea1", {
            opacity: 0,
            y: 10,
            duration: 0.5,
            delay: 0.3,
            ease: "power2.out"
        });

        gsap.from(".form-check", {
            opacity: 0,
            y: 10,
            duration: 0.5,
            delay: 0.4,
            ease: "power2.out"
        });

        gsap.from(".btn-lg", {
            opacity: 0,
            y: 10,
            duration: 0.5,
            delay: 0.5,
            ease: "power2.out"
        });

        $("#exampleFormControlTextarea1").off("input").on("input", function() {
            var searchTerm = $("#exampleFormControlTextarea1").val();
            $.ajax({
                url: "/search_4todelal.php",
                method: "POST",
                data: { search: searchTerm },
                success: function(data) {
                    $("#search-results").empty();
                    if (data.length > 0) {
                        var results = JSON.parse(data);
                        for (var i = 0; i < results.length; i++) {
                            $("#search-results").append("<li class='search-result list-group-item'>" + results[i].text + "</li>");
                        }
                        $(".search-result").click(function() {
                            var selectedText = $(this).text();
                            $("#exampleFormControlTextarea1").val(selectedText);
                            $("#search-results").empty();
                        });
                        gsap.from(".search-result", {
                            opacity: 0,
                            y: 5,
                            duration: 0.3,
                            stagger: 0.05,
                            ease: "power2.out"
                        });
                    }
                }
            });
        });
    });
});
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html>