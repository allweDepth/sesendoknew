class PermissionEngine {

    static can(role, allowed = []) {
        return allowed.includes(role);
    }

}