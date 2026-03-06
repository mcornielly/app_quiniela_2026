// Funciones de formato
export const capitalize = (v) =>
    v.charAt(0).toUpperCase() + v.slice(1)


// Función para convertir un plural a singular (muy básica, no cubre todos los casos)
export function singular(word) {
    if (!word) return ''
    // reglas simples
    if (word.endsWith('ies')) {
        return word.slice(0, -3) + 'y'
    }
    if (word.endsWith('s')) {
        return word.slice(0, -1)
    }
    return word
}
