<?php

/**
 * Auth - Control de acceso y autenticación
 *
 * Gestiona login, logout, verificación de rol y permisos.
 */
class Auth
{
    /**
     * Verifica si hay un usuario autenticado.
     */
    public static function check(): bool
    {
        return Session::has('usuario_id');
    }

    /**
     * Devuelve el array con los datos del usuario en sesión.
     */
    public static function user(): ?array
    {
        if (!self::check()) return null;
        return [
            'id'       => Session::get('usuario_id'),
            'nombre'   => Session::get('usuario_nombre'),
            'email'    => Session::get('usuario_email'),
            'rol'      => Session::get('usuario_rol'),
        ];
    }

    public static function id(): ?int
    {
        return Session::get('usuario_id');
    }

    public static function rol(): ?string
    {
        return Session::get('usuario_rol');
    }

    /**
     * Comprueba si el usuario tiene el rol indicado.
     */
    public static function hasRole(string|array $roles): bool
    {
        $userRol = self::rol();
        if ($userRol === null) return false;
        $roles = (array) $roles;
        return in_array($userRol, $roles, true);
    }

    /**
     * Redirige al login si no está autenticado.
     */
    public static function requireAuth(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Debes iniciar sesión para acceder.');
            header('Location: ' . BASE_URL . '/auth/login');
            exit;
        }
    }

    /**
     * Redirige si no tiene el rol requerido.
     */
    public static function requireRole(string|array $roles): void
    {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            Logger::warning('Auth', 'Acceso denegado por rol', [
                'usuario_id' => self::id(),
                'rol'        => self::rol(),
                'requerido'  => $roles,
            ]);
            http_response_code(403);
            include BASE_PATH . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Inicia sesión con email y contraseña.
     * Devuelve true si OK, false si credenciales incorrectas.
     */
    public static function login(string $email, string $password): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.nombre, u.email, u.password_hash, u.activo, r.nombre AS rol
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !$usuario['activo']) return false;
        if (!password_verify($password, $usuario['password_hash'])) return false;

        // Guardar en sesión
        Session::set('usuario_id',     $usuario['id']);
        Session::set('usuario_nombre', $usuario['nombre']);
        Session::set('usuario_email',  $usuario['email']);
        Session::set('usuario_rol',    $usuario['rol']);

        // Actualizar último acceso
        $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id')
            ->execute([':id' => $usuario['id']]);

        Logger::info('Auth', 'Login exitoso', ['usuario_id' => $usuario['id']]);
        return true;
    }

    public static function logout(): void
    {
        Logger::info('Auth', 'Logout', ['usuario_id' => self::id()]);
        Session::destroy();
    }
}
