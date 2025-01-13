/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
    optimizeDeps: {
        exclude: [
            "resources/js/toggle-password-visibility.js",
            "resources/js/three-state-checkbox.js"
        ],
    }
}

