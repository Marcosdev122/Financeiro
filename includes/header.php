<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sistema Financeiro</title>

    <script>
        (function () {
            try {
                var tema = localStorage.getItem("financeiro-tema-v2") || "claro";
                if (tema !== "escuro" && tema !== "claro") {
                    tema = "claro";
                }
                document.documentElement.setAttribute("data-tema", tema);
            } catch (e) {
                document.documentElement.setAttribute("data-tema", "claro");
            }
        })();
    </script>

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css"
        rel="stylesheet">


    <!-- DataTables -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css">


    <!-- CSS DO PROJETO -->

    <link
        rel="stylesheet"
        href="/Financeiro/assets/css/style.css">

</head>

<body>