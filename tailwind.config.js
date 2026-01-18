/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./templates/**/*.mustache",
        "./public/assets/**/*.js",
        "./public/js/components/*.js"
    ],
    theme: {
        extend: {
            colors: {
                'mcag-slate': '#0f172a',
                'mcag-blue': '#3b82f6',
            }
        }
    },
    plugins: [],
}
