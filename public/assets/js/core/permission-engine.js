class PermissionEngine {

    static allow(role, allowed = []) {
        return allowed.includes(role);
    }

}

window.PermissionEngine = PermissionEngine;