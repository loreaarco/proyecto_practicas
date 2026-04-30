<?php $pageTitle = 'Factura — ' . APP_NAME; ?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= BASE_URL ?>/clientes/<?= $factura['cliente_id'] ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 class="page-title" style="font-size:17px"><?= htmlspecialchars($factura['nombre_original']) ?></h1>
        <p class="page-subtitle mb-0">
            Cliente: <a href="<?= BASE_URL ?>/clientes/<?= $factura['cliente_id'] ?>"
                style="color:var(--text);font-weight:500"><?= htmlspecialchars($factura['cliente_nombre']) ?></a>
        </p>
    </div>
</div>

<div class="row g-4">
    <!-- Panel lateral -->
    <div class="col-lg-4">

        <!-- Estado y acciones -->
        <div class="card mb-3">
            <div class="card-body">
                <p style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:10px">
                    Estado de extracción
                </p>
                <?php
                $extractionBadge = [
                    'pendiente'  => ['bg' => 'var(--yellow-bg)',  'color' => 'var(--yellow)',  'label' => 'Pendiente'],
                    'procesando' => ['bg' => 'var(--blue-bg)',    'color' => 'var(--blue)',    'label' => 'Procesando'],
                    'completada' => ['bg' => 'var(--green-bg)',   'color' => 'var(--green)',   'label' => 'Completada'],
                    'error'      => ['bg' => 'var(--red-bg)',     'color' => 'var(--red)',     'label' => 'Error'],
                ];
                $eb = $extractionBadge[$factura['estado_extraccion']] ?? ['bg' => 'var(--border-light)', 'color' => 'var(--text-muted)', 'label' => $factura['estado_extraccion']];
                ?>
                <span class="badge mb-3" style="font-size:12.5px;padding:5px 14px;background:<?= $eb['bg'] ?>;color:<?= $eb['color'] ?>">
                    <?= $eb['label'] ?>
                </span>

                <div class="d-grid gap-2">
                    <a href="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/ver" target="_blank"
                        class="btn btn-outline-secondary">
                        <i class="bi bi-eye"></i> Ver archivo original
                    </a>

                    <?php if (in_array($factura['estado_extraccion'], ['pendiente', 'error'])): ?>
                        <form method="POST" action="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/extraer"
                            onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Extrayendo...'">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-robot"></i> Extraer datos con IA
                            </button>
                        </form>
                        <p style="font-size:12px;color:var(--text-muted);text-align:center;margin:0">
                            Intentos: <?= $factura['intentos_extraccion'] ?>/5
                        </p>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/facturas/<?= $factura['id'] ?>/datos"
                        class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Revisar / editar datos
                    </a>
                </div>
            </div>
        </div>

        <!-- Metadatos -->
        <div class="card">
            <div class="card-body">
                <dl class="row mb-0" style="row-gap:10px">
                    <?php
                    $meta = [
                        'Subido'  => date('d/m/Y H:i', strtotime($factura['created_at'])),
                        'Por'     => $factura['subida_por_nombre'],
                        'Tamaño'  => round($factura['tamanio_bytes'] / 1024, 1) . ' KB',
                        'Tipo'    => $factura['mime_type'],
                    ];
                    foreach ($meta as $lbl => $val): ?>
                        <dt class="col-5" style="font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);padding-top:0">
                            <?= $lbl ?>
                        </dt>
                        <dd class="col-7 mb-0" style="font-size:13px"><?= htmlspecialchars($val) ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Datos extraídos -->
    <div class="col-lg-8">
        <?php if ($datos): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table me-2" style="color:var(--text-muted)"></i>Datos extraídos</span>
                    <?php if ($datos['revisado_manual']): ?>
                        <span class="badge" style="background:var(--green-bg);color:var(--green)">
                            <i class="bi bi-check-circle me-1"></i>Revisado manualmente
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">

                    <!-- Suministro -->
                    <p class="form-section-title mb-3">Titular y suministro</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Titular</div>
                            <div style="font-size:13.5px"><?= htmlspecialchars($datos['titular_nombre'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">CIF/NIF</div>
                            <div style="font-size:13.5px"><?= htmlspecialchars($datos['titular_cif_nif'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Tipo</div>
                            <div style="font-size:13.5px;text-transform:capitalize"><?= htmlspecialchars($datos['tipo_suministro'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-5">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Comercializadora</div>
                            <div style="font-size:13.5px"><?= htmlspecialchars($datos['comercializadora'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Tarifa</div>
                            <div style="font-size:13.5px"><?= htmlspecialchars($datos['tarifa_acceso'] ?? '—') ?></div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">CUPS</div>
                            <div style="font-size:12px;font-family:monospace"><?= htmlspecialchars($datos['cups'] ?? '—') ?></div>
                        </div>
                        <?php if (!empty($datos['direccion_suministro'])): ?>
                            <div class="col-12">
                                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">
                                    <i class="bi bi-geo-alt me-1"></i>Dirección de suministro
                                </div>
                                <div style="font-size:13.5px"><?= htmlspecialchars($datos['direccion_suministro']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($datos['direccion_suministro'])): ?>
                        <div style="height:260px;border-radius:var(--radius-sm);overflow:hidden;border:1px solid var(--border);margin-bottom:16px">
                            <iframe
                                src="https://maps.google.com/maps?q=<?= urlencode($datos['direccion_suministro'] . ', España') ?>&output=embed&hl=es&z=16"
                                width="100%" height="260"
                                style="border:0;display:block"
                                allowfullscreen loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <!-- Periodo y consumo -->
                    <p class="form-section-title mb-3">Periodo y consumo</p>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Periodo</div>
                            <div style="font-size:13.5px">
                                <?php if ($datos['periodo_inicio'] && $datos['periodo_fin']): ?>
                                    <?= date('d/m/Y', strtotime($datos['periodo_inicio'])) ?> — <?= date('d/m/Y', strtotime($datos['periodo_fin'])) ?>
                                    <?php else: ?>—<?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Días</div>
                            <div style="font-size:13.5px"><?= $datos['dias_facturados'] ?? '—' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Consumo total</div>
                            <div style="font-size:13.5px">
                                <?= $datos['consumo_total_kwh'] ? number_format($datos['consumo_total_kwh'], 0, ',', '.') . ' kWh' : '—' ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:3px">Importe total</div>
                            <div style="font-size:22px;font-weight:700;letter-spacing:-.5px;color:var(--text)">
                                <?= $datos['importe_total'] ? number_format($datos['importe_total'], 2, ',', '.') . ' €' : '—' ?>
                            </div>
                        </div>

                        <?php
                        $potencias = [];
                        for ($p = 1; $p <= 6; $p++) {
                            if ($datos["potencia_p{$p}_kw"] ?? null) {
                                $potencias[] = "<span style='background:var(--border-light);border-radius:4px;padding:2px 7px;font-size:12px'>P{$p}: " . number_format($datos["potencia_p{$p}_kw"], 3) . " kW</span>";
                            }
                        }
                        ?>
                        <?php if (!empty($potencias)): ?>
                            <div class="col-12">
                                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text-muted);margin-bottom:6px">Potencias contratadas</div>
                                <div class="d-flex flex-wrap gap-1"><?= implode('', $potencias) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                        <div class="empty-state-title">Sin datos extraídos</div>
                        <p class="empty-state-text">
                            Pulsa <strong>"Extraer datos con IA"</strong> para iniciar el proceso automático.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- EMPIEZA EL MODULO LLM-->
        <?php
        $datosJson = !empty($factura['datos_json']) ? json_decode($factura['datos_json'], true) : [];
        if (!empty($datosJson)):
        ?>
            <div class="card shadow-sm mb-4 border-primary">
                <div class="card-header bg-primary fw-bold text-white">
                    <span class="text-white">
                        <i class="bi bi-robot me-2"></i>Análisis Módulo LLM
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase mb-1" style="font-size: 12px; font-weight: 600;">Mejor Oferta</h6>
                            <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($datosJson['mejor_oferta'] ?? 'No detectada') ?></h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted text-uppercase mb-1" style="font-size: 12px; font-weight: 600;">Ahorro Anual</h6>
                            <h5 class="fw-bold text-success mb-0"><?= isset($factura['max_ahorro']) ? number_format((float)$factura['max_ahorro'], 2, ',', '.') : '0,00' ?> €</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted text-uppercase mb-1" style="font-size: 12px; font-weight: 600;">Comisión</h6>
                            <h5 class="fw-bold text-info mb-0"><?= isset($factura['comision_estimada']) ? number_format((float)$factura['comision_estimada'], 2, ',', '.') : '0,00' ?> €</h5>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-3 p-3 text-black">
                        <i class="bi bi-lightbulb text-warning me-2"></i>
                        <strong>Recomendación:</strong>
                        <?= htmlspecialchars($datosJson['recomendacion'] ?? 'Análisis pendiente o no disponible') ?>
                    </div>

                    <?php if (!empty($datosJson['otras_opciones'])): ?>
                        <div class="mt-4 pt-3 border-top">
                            <h6 class="text-primary text-uppercase mb-3" style="font-size: 12px; font-weight: 700; letter-spacing: 0.5px;">
                                <i class="bi bi-list-stars me-1"></i>Otras 3 mejores opciones recomendadas
                            </h6>
                            <div class="row g-3">
                                <?php foreach (array_slice($datosJson['otras_opciones'], 0, 3) as $opcion): ?>
                                    <div class="col-md-4">
                                        <div class="p-2 rounded border bg-light h-100 shadow-sm" style="border-left: 4px solid #0dcaf0 !important;">
                                            <div class="fw-bold text-dark mb-1" style="font-size: 13px; line-height: 1.2;">
                                                <?= htmlspecialchars($opcion['nombre_oferta']) ?>
                                            </div>
                                            <div class="text-muted mb-2" style="font-size: 11px;">
                                                <?= htmlspecialchars($opcion['comercializadora']) ?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-success-soft text-success border border-success px-2 py-1" style="font-size: 10px;">
                                                    Ahorro: +<?= number_format((float)($opcion['ahorro_estimado'] ?? 0), 0, ',', '.') ?>€
                                                </span>
                                                <span class="badge bg-info-soft text-info border border-info px-2 py-1" style="font-size: 10px;">
                                                    Comis: <?= number_format((float)($opcion['comision_estimada'] ?? 0), 0, ',', '.') ?>€
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- ============================= -->
            <!-- CHAT INTERACTIVO LLM (POP-UP) -->
            <!-- ============================= -->
            <!-- ============================= -->
            <!-- ESTILOS DEL BOTÓN Y VENTANA -->
            <!-- ============================= -->
            <style>
                /* Botón flotante que abre el chat */
                #chat-popup-btn {
                    position: fixed;
                    /* Siempre visible en pantalla */
                    bottom: 30px;
                    /* Distancia desde abajo */
                    right: 30px;
                    /* Distancia desde la derecha */
                    width: 60px;
                    /* Ancho del botón */
                    height: 60px;
                    /* Alto del botón */
                    border-radius: 50%;
                    /* Forma circular */
                    background: #c41e3a;
                    /* Color principal */
                    color: white;
                    /* Color del icono */
                    border: none;
                    box-shadow: 0 8px 24px rgba(196, 30, 58, 0.3);
                    /* Sombra */
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 26px;
                    /* Tamaño del icono */
                    cursor: pointer;
                    z-index: 1050;
                    /* Prioridad visual */
                    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }

                /* Efecto al pasar el ratón */
                #chat-popup-btn:hover {
                    transform: scale(1.1) rotate(5deg);
                    box-shadow: 0 12px 28px rgba(196, 30, 58, 0.4);
                }

                /* Ventana principal del chat */
                #chat-popup-window {
                    position: fixed;
                    bottom: 100px;
                    right: 30px;
                    width: 380px;
                    max-width: calc(100vw - 60px);
                    height: 550px;
                    max-height: calc(100vh - 150px);
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.15);
                    display: none;
                    /* Oculto por defecto */
                    flex-direction: column;
                    z-index: 1050;
                    overflow: hidden;
                    border: 1px solid rgba(0, 0, 0, 0.08);
                    /* Animación de apertura */
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    opacity: 0;
                    transform: translateY(20px) scale(0.95);
                    transform-origin: bottom right;
                }

                /* Estado activo (cuando el chat está abierto) */
                #chat-popup-window.active {
                    display: flex;
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }

                /* Cabecera del chat */
                .chat-header {
                    padding: 16px 20px;
                    background: #c41e3a;
                    color: white;
                    font-weight: 600;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                /* ============================= */
                /* MENSAJES DEL CHAT             */
                /* ============================= */
                /* Mensajes de la IA */
                .chat-message.ai .message-content {
                    background: #c41e3a;
                    color: white;
                    border-radius: 18px 18px 18px 4px;
                }

                /* Mensajes del usuario */
                .chat-message.user .message-content {
                    background: black;
                    color: white;
                    border-radius: 18px 18px 4px 18px;
                }

                /* Contenido general del mensaje */
                .message-content {
                    padding: 10px 16px;
                    font-size: 14px;
                    line-height: 1.4;
                    display: inline-block;
                    max-width: 85%;
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
                }

                /* ============================= */
                /* ANIMACIÓN DE ESCRITURA        */
                /* ============================= */
                .typing-indicator {
                    display: inline-flex;
                    align-items: center;
                    gap: 3px;
                    margin-left: 4px;
                }

                .typing-dot {
                    width: 5px;
                    height: 5px;
                    background-color: currentColor;
                    /* Inherit text color, e.g., white */
                    border-radius: 50%;
                    animation: typingBounce 1.4s infinite ease-in-out both;
                }

                .typing-dot:nth-child(1) {
                    animation-delay: -0.32s;
                }

                .typing-dot:nth-child(2) {
                    animation-delay: -0.16s;
                }

                .typing-dot:nth-child(3) {
                    animation-delay: 0s;
                }

                @keyframes typingBounce {

                    0%,
                    80%,
                    100% {
                        transform: scale(0);
                        opacity: 0.5;
                    }

                    40% {
                        transform: scale(1);
                        opacity: 1;
                    }
                }

                /* ============================= */
                /* RESPONSIVIDAD PARA MÓVILES    */
                /* ============================= */
                @media (max-width: 576px) {

                    /* Botón más pequeño */
                    #chat-popup-btn {
                        bottom: 20px;
                        right: 20px;
                        width: 55px;
                        height: 55px;
                    }

                    /* Chat ocupa toda la pantalla */
                    #chat-popup-window {
                        bottom: 0;
                        right: 0;
                        width: 100%;
                        height: 100%;
                        max-width: 100%;
                        max-height: 100%;
                        border-radius: 0;
                        z-index: 1100;
                    }

                    #chat-popup-window.active {
                        transform: none;
                    }

                    .chat-header {
                        padding: 20px;
                    }
                }
            </style>
            <!-- ============================= -->
            <!-- BOTÓN PARA ABRIR EL CHAT -->
            <!-- ============================= -->
            <button id="chat-popup-btn"
                onclick="toggleChat()"
                title="Consultar a la IA">
                <!-- Icono del chat -->
                <i class="bi bi-chat-dots"></i>
            </button>
            <!-- ============================= -->
            <!-- VENTANA PRINCIPAL DEL CHAT -->
            <!-- ============================= -->
            <div id="chat-popup-window">
                <!-- Cabecera del chat -->
                <div class="chat-header">
                    <!-- Título -->
                    <span>
                        <i class="bi bi-robot me-2"></i>
                        CONSULTOR IA
                    </span>
                    <!-- Botón cerrar -->
                    <button class="btn btn-sm text-white p-0"
                        onclick="toggleChat()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <!-- ============================= -->
                <!-- CONTENEDOR DE MENSAJES -->
                <!-- ============================= -->
                <div id="chat-container"
                    class="p-3 flex-grow-1"
                    style="overflow-y: auto; background: #ffffff;">
                    <!-- Mensaje inicial de la IA -->
                    <div class="chat-message ai mb-3">
                        <div class="message-content">
                            Hola, soy tu consultor energético. He analizado esta factura. ¿Tienes alguna duda sobre los cálculos o las recomendaciones?
                        </div>
                    </div>
                </div>
                <!-- ============================= -->
                <!-- FORMULARIO PARA ENVIAR MENSAJE -->
                <!-- ============================= -->
                <div class="p-3 border-top bg-white">
                    <form id="chat-form"
                        class="d-flex gap-2">
                        <!-- Campo de texto -->
                        <input type="text"
                            id="chat-input"
                            class="form-control form-control-sm"
                            placeholder="Escribe tu duda aquí..."
                            required
                            autocomplete="off">
                        <!-- Botón enviar -->
                        <button type="submit"
                            class="btn btn-info btn-sm text-white"
                            style="background-color: #c41e3a;">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
            <script>
                /* ===================================== */
                /* FUNCIÓN PARA ABRIR / CERRAR EL CHAT */
                /* ===================================== */
                function toggleChat() {
                    const win = document.getElementById('chat-popup-window');
                    /* Alterna la clase active */
                    win.classList.toggle('active');
                    /* Si el chat está abierto */
                    if (win.classList.contains('active')) {
                        /* Poner foco en el input */
                        document.getElementById('chat-input').focus()
                        /* Scroll al final */
                        const container = document.getElementById('chat-container');
                        container.scrollTop = container.scrollHeight;
                    }
                }
                /* ===================================== */
                /* ENVÍO DE MENSAJES AL SERVIDOR (IA) */
                /* ===================================== */
                document.getElementById('chat-form')
                    .addEventListener('submit', async function(e) {
                        /* Evita recargar la página */
                        e.preventDefault();
                        const input = document.getElementById('chat-input');
                        const container = document.getElementById('chat-container');
                        /* Obtener mensaje */
                        const mensaje = input.value.trim();
                        if (!mensaje) return;
                        /* ============================= */
                        /* MOSTRAR MENSAJE DEL USUARIO */
                        /* ============================= */
                        const userMsg = document.createElement('div');
                        userMsg.className =
                            'chat-message user mb-3 text-end';
                        userMsg.innerHTML =
                            `<div class="message-content">
            ${mensaje}
        </div>`;
                        container.appendChild(userMsg);
                        input.value = '';
                        container.scrollTop =
                            container.scrollHeight;
                        /* ============================= */
                        /* INDICADOR DE CARGA */
                        /* ============================= */
                        const loadingMsg =
                            document.createElement('div');
                        loadingMsg.className =
                            'chat-message ai mb-3';
                        loadingMsg.innerHTML =
                            `<div class="message-content text-white" style="opacity: 0.8;">
            <em>
                Pensando
                <div class="typing-indicator">
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                    <span class="typing-dot"></span>
                </div>
            </em>
        </div>`;
                        container.appendChild(loadingMsg);
                        container.scrollTop =
                            container.scrollHeight;
                        /* ============================= */
                        /* LLAMADA AL BACKEND */
                        /* ============================= */
                        try {
                            // Eliminamos posible barra final del pathname antes de añadir /chat
                            const cleanPath = window.location.pathname.endsWith('/') ?
                                window.location.pathname.slice(0, -1) :
                                window.location.pathname;
                            const response = await fetch(window.location.origin + cleanPath + '/chat', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'mensaje=' +
                                    encodeURIComponent(mensaje)
                            });
                            const data =
                                await response.json();
                            /* Eliminar indicador */
                            loadingMsg.remove();

                            /* Cargar marked si no está */
                            if (typeof marked === 'undefined') {
                                const script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
                                document.head.appendChild(script);
                            }

                            /* Mostrar respuesta IA */
                            const aiMsg = document.createElement('div');
                            aiMsg.className = 'chat-message ai mb-3';

                            const renderResponse = () => {
                                const htmlContent = typeof marked !== 'undefined' ? marked.parse(data.respuesta) : data.respuesta;
                                aiMsg.innerHTML = `<div class="message-content text-white">${htmlContent}</div>`;
                                container.appendChild(aiMsg);
                                container.scrollTop = container.scrollHeight;
                            };

                            if (typeof marked !== 'undefined') {
                                renderResponse();
                            } else {
                                setTimeout(renderResponse, 300);
                            }

                        } catch (error) {
                            /* Error de conexión */
                            console.error('Error chat:', error);
                            loadingMsg.innerHTML =
                                `<div class="p-2 rounded bg-danger text-white small">
                                    Error de conexión: ${error.message}. Revisa la consola del navegador.
                                </div>`;
                        }
                    });
            </script>
        <?php endif; ?>
    </div>
</div>