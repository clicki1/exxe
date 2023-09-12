<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
            crossorigin="anonymous"></script>
    <title>Document</title>
    <!-- Scripts -->
<body>
<?php
//переменая url
if (isset($_GET) && !empty($_GET['time'])) {
    $url_param = explode('?', $_SERVER['REQUEST_URI'], 2);
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $url_param[0];
} else {
    $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}
?>
<div id="app" class="p-5" data-v-app="">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8"><h3>ДОБАВТЕ CSV файл</h3></div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form action="<?= $url ?>" enctype="multipart/form-data" method="post">
                    <div class="input-group mb-3">
                        <input type="file" name="file" class="form-control">
                    </div>
                    <button class="btn btn-primary" type="submit" name="submit">ОБРАБОТАТЬ</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
//Переменный и константы
$time = time();
define("HOSTNAME", "localhost");
define("USERNAME", "u2232676_newroot");
define("PASSWORD", "u2232676_newroot");
define("DATABASE", "u2232676_excel_db");
$alert = '';
//Принимаем запрос
if (!empty($_POST) && isset($_FILES['file'])) {

    $catalog = [
        '0' => 'code',
        '1' => 'title',
        '2' => 'level1',
        '3' => 'level2',
        '4' => 'level3',
        '5' => 'price',
        '6' => 'price_sp',
        '7' => 'count_prod',
        '8' => 'property',
        '9' => 'purchases',
        '10' => 'units',
        '11' => 'img',
        '12' => 'main_page',
        '13' => 'description',
    ];
    $uploaddir = '';
    $uploadfile = $uploaddir . basename($_FILES['file']['name']);


    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        $alert = "Файл " . $_FILES['file']['name'] . " корректен и был успешно загружен.<br>";
    }

    $link = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);

    if ($link == false) {
        $alert = "Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error();
    }

    if (($handle = fopen($uploadfile, "r")) !== FALSE) {
        $item = 0;
        $arr_false = [];
        $flag = true;
        while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {

            if ($flag) {
                $flag = false;
                continue;
            };

            $num = count($data);
            $all_exp = [];
            $all_exp_sort = [];
            for ($c = 0; $c < $num; $c++) {
                $exp = explode(';', $data[$c]);
                $exp_replace = preg_replace('/<br>/', ' ', $exp);
                $all_exp = [...$all_exp, ...$exp_replace];
            }

//
            //ячейки с 0-5
            $check = 0;
            $fl = 0;
            foreach ($all_exp as $k => $a_ex) {

                if (preg_match("/[\(\)\.x\d\-]/", $a_ex) && $check > 1) {
                    $all_exp_sort[$check - 1] = $all_exp_sort[$check - 1] . ' ' . $a_ex;
                    continue;
                }
                if (preg_match("/^[а-я]/u", $a_ex) && $check > 1) {
                    $all_exp_sort[$check - 1] = $all_exp_sort[$check - 1] . ' ' . $a_ex;
                    continue;
                }

                $all_exp_sort[$check] = $a_ex;
                $check++;

                if ($check === 5) break;
            }
            //end ячейки с 0-5
            //ячейки с 5-9
            $check = 0;
            foreach ($all_exp as $k => $a_ex) {

                if (is_numeric($a_ex)) {
                    $all_exp_sort[5 + $check] = $a_ex;
                    $check++;
                }
                if ($check > 5) break;

            }
            //end ячейки с 5-9
            // ячейки с 10 - 13
            $check = 0;
            foreach ($all_exp as $k => $a_ex) {
                if ($check > 0) {

                    if ($check < 4) {
                        $all_exp_sort[10 + $check] = $a_ex;
                        $check++;
                    }

                    if (empty($a_ex) && $check > 3) {
                        break;
                    }
                    if ($check > 3) {
                        $all_exp_sort[13] = $all_exp_sort[13] . ' ' . $a_ex;
                    }

                }
                if (str_starts_with($a_ex, "\"") !== false) {
                    $all_exp_sort[10] = $a_ex;
                    $check = 1;
                }

            }
//            //end ячейки с 10-13

            if (!empty($all_exp_sort[0]) && !empty($all_exp_sort[1])) {
                $link = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);
                $sql = 'INSERT INTO excel_table SET 
                            ' . $catalog[0] . ' = "' . $all_exp_sort[0] . '", 
                            ' . $catalog[1] . ' = "' . addslashes(($all_exp_sort[1] . ' ')) . '", 
                            ' . $catalog[2] . ' = "' . addslashes(($all_exp_sort[2] . ' ')) . '", 
                            ' . $catalog[3] . ' = "' . ($all_exp_sort[3] ?? ' ') . '", 
                            ' . $catalog[4] . ' = "' . ($all_exp_sort[4] ?? ' ') . '", 
                            ' . $catalog[5] . ' = "' . ($all_exp_sort[5] ?? 0) . '", 
                            ' . $catalog[6] . ' = "' . ($all_exp_sort[6] ?? 0) . '", 
                            ' . $catalog[7] . ' = "' . ($all_exp_sort[7] ?? 0) . '", 
                            ' . $catalog[8] . ' = "' . ($all_exp_sort[8] ?? '00') . '", 
                            ' . $catalog[9] . ' = "' . ($all_exp_sort[9] ?? 0) . '", 
                            ' . $catalog[10] . ' = ' . ($all_exp_sort[10] ?? '"усл.ед."') . ', 
                            ' . $catalog[11] . ' = "' . ($all_exp_sort[11] ?? ' ') . '", 
                            ' . $catalog[12] . ' = "' . ($all_exp_sort[12] ?? ' ') . '", 
                            ' . $catalog[13] . ' = "' . addslashes(($all_exp_sort[13] . ' ')) . '", 
                            timestamp =  ' . $time . ' ';
                $result = mysqli_query($link, $sql);
            }
        }
        fclose($handle);
        $button = "
        <div class='alert alert-secondary' role='alert'>
                    $alert
        </div>
        <div class='row'>
            <div class='col-12'>
                <a href=" . $url . "?time=$time  class='btn btn-success btn-lg' aria-current='page'>ПОCМОТРЕТЬ РЕЗУЛЬТАТ</a>
            </div>
        </div>
        ";
        echo $button;

    }
}
?>
<!--Вывод таблицы-->
<?php if (isset($_GET) && !empty($_GET['time'])): ?>
    <?php
    $link = mysqli_connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);
    if ($link == false) {
        print("Ошибка: Невозможно подключиться к MySQL " . mysqli_connect_error());
    }
    $time = $_GET['time'];

    $sql = 'SELECT * FROM excel_table WHERE timestamp =  ' . $time . ' ';

    $result = mysqli_query($link, $sql);

    if ($result == false) {
        print("Произошла ошибка при выполнении запроса");
    }
    ?>
    <table class="table">
        <thead>

        <tr>
            <th scope="col">Koд</th>
            <th scope="col">Наименование</th>
            <th scope="col">Уровень1</th>
            <th scope="col">Уровень2</th>
            <th scope="col">Уровень3</th>
            <th scope="col">Цена</th>
            <th scope="col">ЦенаСП</th>
            <th scope="col">Количество</th>
            <th scope="col">Поля свойств</th>
            <th scope="col">Совместные покупки</th>
            <th scope="col">Единица измерения</th>
            <th scope="col">Картинка</th>
            <th scope="col">Выводить на главной</th>
            <th scope="col">Описание</th>
        </tr>

        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_array($result)): ?>

            <tr>
                <th scope="row"><?= $row['code']; ?></th>
                <td><?= $row['title']; ?></td>
                <td><?= $row['level1']; ?></td>
                <td><?= $row['level2']; ?></td>
                <td><?= $row['level3']; ?></td>
                <td><?= $row['price']; ?></td>
                <td><?= $row['price_sp']; ?></td>
                <td><?= $row['count_prod']; ?></td>
                <td><?= $row['property']; ?></td>
                <td><?= $row['purchases']; ?></td>
                <td>"<?= $row['units']; ?>"</td>
                <td><?= $row['img']; ?></td>
                <td><?= $row['main_page']; ?></td>
                <td><?= $row['description']; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>


</body>
</html>
