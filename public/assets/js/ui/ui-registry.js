// ======================================================
// UI COMPONENT REGISTRY
// ======================================================

class UIComponentRegistry {

    static registry = {};

    static register(tag, renderer) {
        this.registry[tag] = renderer;
    }

    static render(element) {

        const { tag, prop } = element;

        if (!this.registry[tag]) {
            console.warn("Component tidak terdaftar:", tag);
            return "";
        }

        return this.registry[tag](prop);
    }
}