<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <link rel="stylesheet" href="{{ asset('docs/css/styles.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="dark dark:bg-dark-800">
<div class="app-wrapper min-h-screen bg-slate-50 dark:bg-dark-700">
    <!-- Header Section with API Info -->
    <header id="documentConfig" class="app-header bg-white dark:bg-dark-800 p-6 border-b border-slate-200 dark:border-dark-700">
        <div class="header-content w-full mx-auto">
            <div class="header-top flex justify-between items-center mb-6">
                <div class="api-info">
                    <h1 class="api-title text-2xl font-bold mb-2 text-slate-900 dark:text-dark-50"></h1>
                    <p class="api-description text-slate-600 dark:text-dark-400"></p>
                </div>
                <!-- Theme Toggle -->
                <button class="theme-toggle p-2 rounded-md bg-slate-100 dark:bg-dark-700 hover:bg-slate-200 dark:hover:bg-dark-600 transition-colors">
                    <!-- Light mode icon -->
                    <svg class="w-6 h-6 text-slate-600 dark:text-dark-400 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <!-- Dark mode icon -->
                    <svg class="w-6 h-6 text-slate-600 dark:text-dark-400 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </button>
            </div>

            <!-- API Info Grid -->
            <div class="api-info-grid flex items-center gap-8">
                <!-- Version Info -->
                <div class="version-info flex-1">
                    <h2 class="info-title text-sm font-medium text-slate-700 dark:text-dark-300 mb-2">Version</h2>
                    <div class="version-value bg-slate-50 dark:bg-dark-700 px-3 py-2 rounded-md text-sm text-slate-900 dark:text-dark-100"></div>
                </div>

                <!-- Server URLs -->
                <div class="server-info flex-[2]">
                    <h2 class="info-title text-sm font-medium text-slate-700 dark:text-dark-300 mb-2">Server URL</h2>
                    <div class="server-list space-y-2">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Authentication -->
                <div class="auth-info flex-1">
                    <h2 class="info-title text-sm font-medium text-slate-700 dark:text-dark-300 mb-2">Authentication</h2>
                    <div class="auth-container py-2 text-sm bg-slate-50 dark:bg-dark-700 py-2 px-3 rounded-md">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                <div class="authorization ">

                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="app-main flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="app-sidebar w-[400px] bg-white dark:bg-dark-800 border-r border-slate-200 dark:border-dark-700">
            <!-- Search Box -->
            <div class="search-container p-4 border-b border-slate-200 dark:border-dark-700">
                <!-- Will be populated by JavaScript -->
            </div>
            <!-- API Sections -->
            <div class="api-sections overflow-y-auto max-h-[calc(100vh-73px)]">
                <!-- Will be populated by JavaScript -->
            </div>
        </aside>

        <!-- Documentation Content -->
        <div class="w-[calc(100%_-_400px)] min-h-screen bg-white dark:bg-dark-800">
            <section id="mainContent" class="documentation-content overflow-auto">
                <div class="content-wrapper mx-auto space-y-6">
                    <!-- Will be populated by JavaScript -->
                </div>
            </section>

            <!-- Test Panel -->
            <aside class="test-panel w-full border-t border-slate-200 dark:border-dark-700 bg-white dark:bg-dark-800">
                <!-- Will be populated by JavaScript -->
            </aside>
        </div>
    </main>
</div>

<script>
    window.BASE_URL = `{{ url('/') }}`;
</script>
<script type="module" src="{{ asset('docs/js/main.js') }}"></script>
</body>
</html>
