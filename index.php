<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/styles.css?v=<?php echo time(); ?>">
</head>

<?php
include 'assets/php/config/database.php';
include 'assets/php/actions/get_registers.php';
include 'assets/php/actions/get_statistics.php';

session_start();

$user = $_SESSION['user'];

if (!isset($user)) {
    header('Location: /login.php');
}

$db = conectarDB();

$registers = getRegisters($db);
$statistics = getStatistics($db);
?>

<body>
    <div class="headbar"></div>
    <div class="sidebar">
        <h5>Início</h5>
        <div class="logoSidebar">
            <img src="assets/img/logo.jfif" alt="">
            <p>GCC Technology</p>
        </div>
        <a href="#" class="dashboardicon"> <i class="fa-solid fa-table-columns"></i>Filtro Moviles</a>
        <a href="login.php" class="exit"><i class="fa-solid fa-arrow-right-from-bracket"></i> Salir</a>
    </div>

    <div class="content">
        <div class="row">
            <div class="col-md-3">
                <div class="card card-primary mb-3">
                    <div class="card-body">
                        <div>
                            <h5 class="card-title">Bases Sin Iniciar</h5>
                            <div class="card-content">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <p class="card-text"><?php echo $statistics['notInit']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Bases Finalizadas</h5>
                        <div class="card-content">
                            <i class="fa-solid fa-flag-checkered"></i>
                            <p class="card-text"><?php echo $statistics['finished']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Bases en Proceso</h5>
                        <div class="card-content">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            <p class="card-text"><?php echo $statistics['inProcess']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Bases Detenidas</h5>
                        <div class="card-content">
                            <i class="fa-solid fa-wallet"></i>
                            <p class="card-text"><?php echo $statistics['inPause']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex">
            <input type="text" class="form-control mb-2" id="search" placeholder="Buscar...">

            <button class="btn-search btn btn-primary" style="margin-left: 20px;"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>

        <div class="d-flex">
            <p class="resoult-consult"></p>
        </div>

        <form class="d-flex-process" action="assets/php/actions/charge_excel.php" method="post" enctype="multipart/form-data">
            <input type="file" class="form-control mb-2" name="excelFile" id="excelFile" accept=".xls, .xlsx" required>
            <button class="btn-process" type="submit">PROCESAR</button>
        </form>

        <div class="card-table">
            <div class="card-body">
                <div class="d-flex-title">
                    <h5 class="card-title">Filtro Moviles</h5>
                </div>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Código</th>
                            <th>Nombre Base</th>
                            <th>Fecha Carga</th>
                            <th># Registros</th>
                            <th># Procesados</th>
                            <!-- <th>Progreso</th> -->
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- <tr>
                            <td style="font-weight: bold;">1</td>
                            <td>prueba_moviles_07082024_zsElsRu.xlsx</td>
                            <td>2024-08-21T21:37:41.093879Z</td>
                            <td>1000</td>
                            <td>
                                <div class="progress-container">
                                    <progress class="progress-bar" value="21" max="100"></progress>
                                    <span class="progress-text">21%</span>
                                </div>
                            </td> 
                            <td>
                               <p class="text-proceso">En Proceso</p> 
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">2</td>
                            <td>prueba_moviles_07082024_hsULTpF.xlsx</td>
                            <td>2024-08-21T21:40:12.093879Z</td>
                            <td>1000</td>
                            <!-- <td>
                                <div class="progress-container">
                                    <progress class="progress-bar" value="3" max="100"></progress>
                                    <span class="progress-text">3%</span>
                                </div>
                            </td> 
                            <td>
                                <p class="text-proceso">En Proceso</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;">3</td>
                            <td>prueba_moviles_07082024_kLyuIOp.xlsx</td>
                            <td>2024-08-21T21:50:37.093879Z</td>
                            <td>1000</td>
                            <!-- <td>
                                <div class="progress-container">
                                    <progress class="progress-bar" value="100" max="100"></progress>
                                    <span class="progress-text">100%</span>
                                </div>
                            </td>
                            <td>
                                <p class="text-finalizada">Finalizada</p>
                            </td>
                        </tr> -->

                        <?php foreach ($registers as $register) : ?>
                            <tr>
                                <td style="font-weight: bold;"><?= $register['id'] ?></td>
                                <td><?= $register['codigo'] ?></td>
                                <td><?= $register['nombre'] ?></td>
                                <td><?= $register['fecha'] ?></td>
                                <td><?= $register['registros'] ?></td>
                                <td class="procesados" id="<?= $register['id'] ?>">
                                    <?= $register['registros_procesados'] ?>
                                </td>
                                <!-- <td>
                                    <div class="progress-container">
                                        <progress class="progress-bar" value="21" max="100"></progress>
                                        <span class="progress-text">21%</span>
                                    </div>
                                </td> -->
                                <?php
                                $statusClass = '';
                                $buttonText = '';
                                $buttonClass = '';

                                switch ($register['estado']) {
                                    case 'CARGADO':
                                        $statusClass = 'text-proceso';
                                        $buttonText = 'INICIAR';
                                        $buttonClass = 'btn btn-success';
                                        $style = 'background-color: #3085b6;';
                                        $type = '2';
                                        break;
                                    case 'EN PROCESO':
                                        $statusClass = 'text-proceso';
                                        $buttonText = 'PAUSAR';
                                        $buttonClass = 'btn btn-danger';
                                        $type = '3';
                                        break;
                                    case 'PAUSADO':
                                        $statusClass = 'text-proceso';
                                        $buttonText = 'REANUDAR';
                                        $buttonClass = 'btn btn-success';
                                        $style = 'background-color: #f54b4b;';
                                        $type = '2';
                                        break;
                                    case 'FINALIZADO':
                                        $statusClass = 'text-finalizada';
                                        $style = 'background-color: #28a745;';
                                        break;
                                    default:
                                        $statusClass = '';
                                        $buttonText = '';
                                        $buttonClass = '';
                                        break;
                                }
                                ?>

                                <td>
                                    <p class="<?= $statusClass ?>" style="<?= isset($style) ? $style : ''; ?>">
                                        <?= $register['estado'] ?>
                                    </p>
                                </td>

                                <td>

                                    <?php if ($buttonText) : ?>

                                        <button onclick="changeStateRegister(<?= $register['id'] ?>, <?= $type ?>)" class="<?= $buttonClass ?>" type="button">
                                            <?= $buttonText ?>
                                        </button>

                                    <?php endif; ?>
                                    <button class="btn-process"><a style="color: #fff; text-decoration: none;" href="assets/php/actions/download_excel.php?id=<?= $register['id'] ?>">DESCARGAR</a></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        procesarProcesados();
        setInterval(function() {
            procesarProcesados();
        }, 5000);

        $('.btn-search').click(
            function() {
                let value = $('#search').val();

                if (!value.length) {
                    return;
                }

                $.ajax({
                    type: 'GET',
                    url: 'http://numeracionyoperadores.cnmc.es/api/portabilidad/movil?numero=' + value + '&captchaLoad=test',
                    success: function(data) {
                        $('.resoult-consult').text(data.operador.nombre);
                    },
                    error: function(error) {
                        alert('Debe introducir un número de teléfono móvil válido')
                    }
                })
            }
        )

        function changeStateRegister(id, type) {
            $.ajax({
                type: 'GET',
                url: 'assets/php/actions/change_state_register.php?id=' + id + '&type=' + type,
                success: function(data) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: data
                    }).then(() => {
                        location.reload();
                    })
                },
                error: function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Algo salió mal'
                    })
                }
            })
        }

        function procesarProcesados() {
            $('.procesados').each(function() {
                let procesadoId = $(this).attr('id');

                $.get('assets/php/actions/get_procesados.php?id=' + procesadoId, function(data) {
                    $('#' + procesadoId).html(data);

                    $('.progress-bar').each(function(index) {
                        const $this = $(this);
                        const percentage = $this.val();
                        const uniqueId = 'progressBar-' + index;
                        $this.attr('id', uniqueId);
                        updateProgressColor($this, percentage);
                    });
                });
            });
        }

        function updateProgressColor($progressBar, percentage) {
            const red = Math.round(255 - (percentage * 2.55));
            const green = 255;
            const blue = 0;

            const color = `rgb(${red}, ${green}, ${blue})`;

            $progressBar.css('background-color', '#EEE');
            $progressBar.css('color', color);

            const progressBarId = $progressBar.attr('id');
            const styleElement = document.createElement('style');
            styleElement.innerHTML = `
                #${progressBarId}::-webkit-progress-value { background-color: ${color}; }
                #${progressBarId}::-moz-progress-bar { background-color: ${color}; }
            `;
            document.head.appendChild(styleElement);
        }

        $(document).ready(function() {
            $('.progress-bar').each(function(index) {
                const $this = $(this);
                const percentage = $this.val();
                const uniqueId = 'progressBar-' + index;
                $this.attr('id', uniqueId);
                updateProgressColor($this, percentage);
            });
        });
    </script>
</body>

</html>