<?php
session_start();
include("inc/function.php");
echo '<!doctype html><html lang="ru">';
include("inc/style.php");
?>
<body style="background: #ffffff url(img/background.webp) repeat;">
<div class="container-sm">
    <!-- Логотип -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="border-radius: 0!important; padding-bottom: 0; background: #ffffff url(img/background.webp) repeat;">
        <div class="container" style="display: initial;">
            <div class="row">
                <div class="col-12">
                    <a class="navbar-brand" href="/index.php">
                        <img id="animated-example" class="mt-2 pidaras animated fadeOut" src="img/logo.webp?12w" alt="ArdMoney" height="90px">
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <?php
    if (isset($_COOKIE['user'])) {
        $loggedInUser = $_COOKIE['user'];
        echo "<script>
            if (window.AndroidInterface) {
                window.AndroidInterface.setUserLogin('$loggedInUser');
            }
        </script>";
    }
    ?>

 
<main role="main" style = "padding-bottom: 60px;" >
        <div style="min-height: calc(100vh - 9rem); padding: 0 0; background: #fff;" class="jumbotron">
            <div class="col-md-12 col-sm-12 mx-auto">
                <?php
                AutorizeProtect();
                access();
                animate();

                $view_complete = '';
                $current_year = date('Y');
                $current_month = date('m');
                $default_year = $current_year;
                $default_month = $current_month;

                if (isset($_GET['older'])) {
                    $default_year = $current_year - 1;
                    $default_month = '12';
                }

                if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}$/', $_GET['date'])) {
                    $date_current = $_GET['date'];
                    list($selected_year, $selected_month) = explode('-', $date_current);
                } else {
                    $selected_year = $default_year;
                    $selected_month = $default_month;
                    $date_current = $selected_year . '-' . $selected_month;
                }

                $month = date_view($date_current);
                ?>

                <title>Монтажи - <?=$month?></title>

                <nav class="navbar navbar-expand-lg navbar-dark bg-dark nav-custom" style="padding: 0; margin-top: 0;">
                    <div class="container-fluid" style="background: #00000059; padding: 0.75rem 0.25rem 0.75rem 0.25rem;">
                        <div class="navbar-collapse d-flex justify-content-between align-items-center" id="navbarNavDarkDropdown">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center">
                                    <select class="form-select form-select-sm" id="year" name="year" style="width: auto;" onchange="loadArchiveData()">
                                        <?php
                                        $start_year = 2022;
                                        $end_year = $current_year;

                                        for ($year = $start_year; $year <= $end_year; $year++) {
                                            $selected = (isset($_GET['older']) && $year == $current_year - 1) || (!isset($_GET['older']) && $year == $selected_year) ? 'selected' : '';
                                            echo "<option value=\"$year\" $selected>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center">
                                    <select class="form-select form-select-sm" id="month" name="month" style="width: auto;" onchange="loadArchiveData()">
                                        <?php
                                        $months = [
                                            '01' => 'Январь',
                                            '02' => 'Февраль',
                                            '03' => 'Март',
                                            '04' => 'Апрель',
                                            '05' => 'Май',
                                            '06' => 'Июнь',
                                            '07' => 'Июль',
                                            '08' => 'Август',
                                            '09' => 'Сентябрь',
                                            '10' => 'Октябрь',
                                            '11' => 'Ноябрь',
                                            '12' => 'Декабрь',
                                        ];
                                        foreach ($months as $key => $name) {
                                            $selected = $key == $selected_month ? 'selected' : '';
                                            echo "<option value=\"$key\" $selected>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <?php
                                
                                    admin_checkbox($usr['id']);
                                
                                ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if($usr['name'] == "tretjak" ){ ?>
                                    <div style="margin-left: auto; display: flex; align-items: center; gap: 1rem;">
                                        <a href="search_montaj.php">
                                            <img src="/img/search.png" alt="Поиск" style="width: 42px; height: 42px;">
                                        </a>
                                        <a href="user.php">
                                            <img src="/img/home.png" alt="Домой" style="width: 42px; height: 42px;">
                                        </a>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </nav>

                <?php
                if (isset($_GET['delete'])) {
                    delete_mon($id);
                }

                if (isset($_GET['complete'])) {
                    $view_complete = " AND `status` = '0'";
                }

                if (isset($_GET['id']) && $_GET['id'] == "ok") {
                    alrt("Успешно удаленно", "success", "2");
                }

                if (isset($_GET['status']) && $_GET['status'] === 'success') {
                    echo '<div class="alert text-center alert-success alert-dismissible fade show" role="alert" id="successAlert">
                            Монтаж успешно подтвержден!
                          </div>';
                    echo '<script>
                        const alert = document.getElementById("successAlert");
                        const bsAlert = new bootstrap.Alert(alert);
                        setTimeout(() => {
                            bsAlert.close();
                        }, 2000);
                    </script>';
                }

                if($usr['name'] == "tretjak" ){
                    ?>
                    <div style="width: 100%;" class="btn-group" role="group" aria-label="Basic outlined example">
                        <a href="montaj.php" class="btn btn-success btn-lg green_button">Добавить монтаж</a>
                    </div>
                    <?php
                }
                ?>

                <div class="input-group">
                    <span class="input-group-text">Поиск</span>
                    <input id="spterm" type="text" aria-label="адрес" class="form-control" oninput="liveSearch()">
                </div>
                <div id="archiveContent">
                    <!-- Здесь будет контент -->
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимации при загрузке страницы
    gsap.from(".nav-custom", {
        opacity: 0,
        duration: 0.5,
        ease: "power2.out"
    });

    gsap.from(".green_button", {
        opacity: 0,
        y: 10,
        duration: 0.5,
        delay: 0.2,
        ease: "power2.out"
    });

    gsap.from(".input-group", {
        opacity: 0,
        duration: 0.5,
        delay: 0.3,
        ease: "power2.out"
    });

    // Получаем GET-параметр date из URL
    const urlParams = new URLSearchParams(window.location.search);
    let selectedYear, selectedMonth;

    if (urlParams.has('date') && /^\d{4}-\d{2}$/.test(urlParams.get('date'))) {
        const dateParam = urlParams.get('date');
        [selectedYear, selectedMonth] = dateParam.split('-');
    } else {
        let currentDate = new Date();
        selectedYear = currentDate.getFullYear().toString();
        selectedMonth = String(currentDate.getMonth() + 1).padStart(2, '0');

        if (urlParams.has('older')) {
            selectedYear = (currentDate.getFullYear() - 1).toString();
            selectedMonth = '12';
        }
    }

    // Устанавливаем значения в выпадающих списках
    document.getElementById('year').value = selectedYear;
    document.getElementById('month').value = selectedMonth;

    // Загружаем данные
    loadArchiveData();
});

function loadArchiveData() {
    let year = document.getElementById('year') ? document.getElementById('year').value : '';
    let month = document.getElementById('month') ? document.getElementById('month').value : '';
    let archiveContent = document.getElementById('archiveContent');

    if (!archiveContent) return;

    archiveContent.innerHTML = `
        <div id="loadingSpinner" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
            <img id="spinnerImg" src="img/baza.png" style="width: 50px;">
        </div>`;
    
    gsap.to("#spinnerImg", { rotation: 360, duration: 1, repeat: -1, ease: "linear" });

    let requestData = { date: year + '-' + month };
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('current_user')) {
        requestData.current_user = urlParams.get('current_user');
    }

    $.ajax({
        url: 'obr_index.php',
        method: 'GET',
        data: requestData,
        success: function(response) {
            archiveContent.innerHTML = response;
            gsap.from(".hui", {
                opacity: 0,
                y: 10,
                duration: 0.15,
                stagger: 0.01,
                ease: "power2.out"
            });
            gsap.from(".badge", {
                opacity: 0,
                scale: 0.5,
                duration: 0.4,
                stagger: 0.05,
                ease: "elastic.out(1, 0.3)",
                delay: 0.2
            });
        },
        error: function() {
            archiveContent.innerHTML = '<div class="alert alert-danger">Ошибка загрузки данных</div>';
        }
    });
}

function liveSearch() {
    let spterm = document.getElementById('spterm');
    if (!spterm) return;

    let searchTerm = spterm.value.toLowerCase();
    let items = document.querySelectorAll('#skrivat');
    
    items.forEach(item => {
        let searchValue = item.querySelector('.search_view').getAttribute('data-value').toLowerCase();
        if (searchValue.includes(searchTerm)) {
            item.style.display = '';
            gsap.fromTo(item, 
                { opacity: 0 },
                { opacity: 1, duration: 0.2, ease: "power2.out" }
            );
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php include 'inc/foot.php'; ?>
</body>
</html>