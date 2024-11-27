<?php
#region DATA
const DATA = [
    'lastnames' => [
        'Major',
        'Riz',
        'Kard',
        'Pum',
        'Víz',
        'Kandisz',
        'Patta',
        'Para',
        'Pop',
        'Remek',
        'Ének',
        'Szalmon',
        'Ultra',
        'Dil',
        'Git',
        'Har',
        'Külö',
        'Harm',
        'Zsíros B.',
        'Virra',
        'Kasza',
        'Budipa',
        'Bekre',
        'Fejet',
        'Minden',
        'Bármi',
        'Lapos',
        'Bor',
        'Mikorka',
        'Szikla',
        'Fekete',
        'Rabsz',
        'Kalim',
        'Békés',
        'Szenyo',
    ],

    'firstnames' => [
        'men' => ['Ottó', 'Pál', 'Elek', 'Simon', 'Ödön', 'Kálmán', 'Áron', 'Elemér', 'Szilárd', 'Csaba'],
        'women' => ['Anna', 'Virág', 'Nóra', 'Zita', 'Ella', 'Viola', 'Emma', 'Áron', 'Mónika', 'Dóra', 'Blanka',
            'Piroska', 'Lenke', 'Mercédesz', 'Olga', 'Rita',]
    ],

    'classes' => [
        '11a', '11b', '11c', '12a', '12b', '12c',
    ],

    'subjects' => ['math', 'history', 'biology', 'chemistry', 'physics', 'informatics', 'alchemy', 'astrology', ],
];

#endregion

session_start();

#region head generation

function generate_head($title = 'Horlik Nimród Imre Szakközép Iskola') {
    return <<<HTML
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title}</title>
        <link rel="stylesheet" href="style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    </head>
HTML;
}
#endregion

#region diakok generalasa
function generate_student($class) {
    $gender = rand(0, 1) == 0 ? 'men' : 'women';
    $first_name = DATA['firstnames'][$gender][array_rand(DATA['firstnames'][$gender])];
    $last_name = DATA['lastnames'][array_rand(DATA['lastnames'])];
    $gender_text = $gender == 'men' ? 'fiú' : 'lány';
    $student = [
        'name' => "$last_name $first_name",  
        'gender' => $gender_text,
        'class' => $class,
        'grades' => [],
    ];

    foreach (DATA['subjects'] as $subject) {
        $num_grades = rand(0, 5);
        if ($num_grades > 0) {
            $student['grades'][$subject] = array_map(fn() => rand(1, 5), range(1, $num_grades));

        } else {
            $student['grades'][$subject] = ['nincs jegy'];
        }
    }
    return $student;
}

#endregion

#region osztalyok generalasa
function generate_class($class_name) {
    $num_students = rand(10, 15); 
    $students = [];
    for ($i = 0; $i < $num_students; $i++) {
        $students[] = generate_student($class_name);
    }

    return $students;
}

#endregion

#region session belerakasa
if (!isset($_SESSION['school'])) {
    $school = [];
    foreach (DATA['classes'] as $class_name) {
        $school[$class_name] = generate_class($class_name);

    }
    $_SESSION['school'] = $school;
} else {
    $school = $_SESSION['school'];
}
#endregion

#region ujra generalasa
if (isset($_POST['regenerate'])) {
    session_destroy();
    session_start();
    $school = [];
    foreach (DATA['classes'] as $class_name) {
        $school[$class_name] = generate_class($class_name);
    }
    $_SESSION['school'] = $school;
}
#endregion

#region oszzes osztaly kiirasa
function print_all_students($school) {
    echo "<div class='grid-container'>";
    foreach ($school as $class_name => $students) {
        usort($students, fn($a, $b) => strcmp($a['name'], $b['name']));
        echo "<div class='class-block'>";
        echo "<h2>Osztály: $class_name</h2>";
        foreach ($students as $student) {
            echo "<p><strong>{$student['name']} ({$student['gender']})</strong><br>";
            foreach ($student['grades'] as $subject => $grades) {
                $grades_text = implode(', ', $grades);
                echo "$subject: $grades_text<br>";
            }
            echo "</p>";

        }
        echo "</div>";

    }
    echo "</div>";

}
#endregion

#region egyes osztalyok kiirasa
function print_class_students($school, $class_name) {
    if (isset($school[$class_name])) {
        usort($school[$class_name], fn($a, $b) => strcmp($a['name'], $b['name']));
        echo "<h2>Osztály: $class_name</h2>";
        foreach ($school[$class_name] as $student) {
            echo "<p><strong>{$student['name']} ({$student['gender']})</strong><br>";
            foreach ($student['grades'] as $subject => $grades) {
                $grades_text = implode(', ', $grades);
                echo "$subject: $grades_text<br>";
            }
            echo "</p>";
        }
    }

}
#endregion

#region CSV export
function ensure_export_directory() {
    $export_dir = 'export';
    if (!file_exists($export_dir) || !is_dir($export_dir)) {
        mkdir($export_dir);
    }
    return $export_dir;
}

function generate_export_filename($class_name) {
    $timestamp = date('Y-m-d_Hi');
    return sprintf('%s-%s.csv', $class_name, $timestamp);
}

function export_class_to_csv($school, $class_name) {
    if (!isset($school[$class_name])) {
        return false;
    }

    $export_dir = ensure_export_directory();
    $filename = generate_export_filename($class_name);
    $filepath = $export_dir . '/' . $filename;

    $file = fopen($filepath, 'w');
    if (!$file) {
        return false;
    }

    $header = ['ID', 'Name', 'Firstname', 'Lastname', 'Gender', 'Subject', 'Marks'];
    fputcsv($file, $header, ';'); 
    foreach ($school[$class_name] as $index => $student) {
        $names = explode(' ', $student['name']);
        $lastname = array_shift($names);
        $firstname = implode(' ', $names);
        $gender_code = $student['gender'] === 'fiú' ? 'M' : 'F';
        $student_id = sprintf('%s-%d', $class_name, $index);

        foreach ($student['grades'] as $subject => $grades) {
            $row = [
                $student_id,
                $student['name'],
                $firstname,
                $lastname,
                $gender_code,
                $subject,
                is_array($grades) ? implode(',', $grades) : $grades
            ];
            fputcsv($file, $row, ';'); 
        }
    }
    fclose($file);
    return $filename;
}


#endregion
?>

<!DOCTYPE html>
<html lang="hu">
    
<?php echo generate_head(); ?>

<body>
    <div id="kint">
        <div class="button-container" id="bent">
            <h1 class="ubuntu-regular">Horlik Nimród Imre Szakközép Iskola</h1>
            
            <div class="class-buttons">
                <button onclick="window.location.href='?view=all'">Összes tanuló</button>
                <button onclick="window.location.href='?view=11a'">11a osztály</button>
                <button onclick="window.location.href='?view=11b'">11b osztály</button>
                <button onclick="window.location.href='?view=11c'">11c osztály</button>
                <button onclick="window.location.href='?view=12a'">12a osztály</button>
                <button onclick="window.location.href='?view=12b'">12b osztály</button>
                <button onclick="window.location.href='?view=12c'">12c osztály</button>
                <form method="POST" class="regenerate-form">
                    <button type="submit" name="regenerate">Újragenerálás</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    if (isset($_GET['view'])) {
        $view = $_GET['view'];
        if ($view == 'all') {
            print_all_students($school);
        } elseif (array_key_exists($view, $school)) {
            print_class_students($school, $view);
            echo '<div class="export-container">';
            echo '<form method="POST" class="export-form">';
            echo '<input type="hidden" name="export_class" value="' . $view . '">';
            echo '<button type="submit">CSV-be exportálás</button>';
            echo '</form>';
            echo '</div>';
        } else {
            echo "<p>Ismeretlen osztály.</p>";
        }
    } else {
        print_all_students($school);
    }

    if (isset($_POST['export_class'])) {
        $class_to_export = $_POST['export_class'];
        if (array_key_exists($class_to_export, $school)) {
            $exported_file = export_class_to_csv($school, $class_to_export);
            if ($exported_file) {
                echo "<p class='export-message success'>Az osztály adatai sikeresen exportálva: $exported_file</p>";
            } else {
                echo "<p class='export-message error'>Hiba történt az exportálás során.</p>";
            }
        }
    }
    ?>
</body>
</html>
