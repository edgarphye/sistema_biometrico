
// Test simple para escapeHtml
function escapeHtml(text) {
    if (text === null || text === undefined || text === "") {
        return "";
    }
    const textStr = String(text);
    const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "'": "&#039;"
    };
    return textStr.replace(/[&<>"']/g, function(match) {
        return map[match] || match;
    });
}

// Probar con diferentes valores
console.log("Test 1 - null:", escapeHtml(null));
console.log("Test 2 - undefined:", escapeHtml(undefined));
console.log("Test 3 - string vacío:", escapeHtml(""));
console.log("Test 4 - texto normal:", escapeHtml("Hola <mundo>"));
console.log("Test 5 - texto con comillas:", escapeHtml('Texto con "comillas"'));
console.log("Test 6 - valor numérico:", escapeHtml(123));
console.log("✅ Todos los tests completos");
