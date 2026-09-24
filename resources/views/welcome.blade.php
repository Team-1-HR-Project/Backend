<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>WorkWise API Services</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },

                    colors: {
                        brand: {
                            dark: '#1E293B',
                            navy: '#0F172A',
                            accent: '#10B981',
                            blue: '#2563EB',
                            bg: '#F8FAFC',
                            card: '#FFFFFF',
                        }
                    }
                }
            }
        }
    </script>

</head>

<body class="bg-[#F8FAFC] text-slate-800 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-teal-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="w-full bg-white border-b border-slate-200/80 sticky top-0 z-50">

        <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">

            <!-- Brand Logo -->
            <div class="flex items-center gap-3">

                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-emerald-600 flex items-center justify-center text-white font-extrabold text-xl shadow-md shadow-emerald-500/20">
                    W
                </div>

                <div class="flex flex-col">

                    <span class="text-xl font-extrabold tracking-tight text-slate-900 leading-none">
                        WiseWork
                    </span>

                    <span class="text-[11px] font-semibold tracking-wider text-emerald-600 uppercase mt-1">
                        HRMS API Platform
                    </span>

                </div>

            </div>

            <!-- Header Badge -->
            <div class="flex items-center gap-2">

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">

                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse mr-1.5"></span>

                    v1.0 Live

                </span>

            </div>

        </div>

    </header>


    <!-- Main Content Area -->
    <main class="max-w-6xl mx-auto px-6 py-12 flex-1 flex flex-col justify-center w-full">

        <!-- Documentation Access Section -->
        <div class="max-w-4xl mx-auto w-full space-y-4">

            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 px-1 text-center">
                API Documentation Interfaces
            </h2>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Scalar Documentation Card -->
                <a
                    href="https://registry.scalar.com/@workwise/apis/hr-api-documentation@1.0.0?format=preview#description/introduction"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group relative bg-white rounded-2xl p-6 lg:p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:border-emerald-500/50 transition-all duration-300 flex flex-col justify-between overflow-hidden"
                >

                    <div class="flex items-start justify-between mb-6">

                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">

                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                />
                            </svg>

                        </div>

                        <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-lg">
                            Recommended
                        </span>

                    </div>


                    <div>

                        <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-emerald-600 transition-colors">
                            Scalar Interactive Docs
                        </h3>

                        <p class="text-slate-500 text-sm leading-relaxed mb-6">
                            Modern, fast, and interactive API documentation view with live endpoint testing and code samples.
                        </p>

                    </div>


                    <div class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 group-hover:translate-x-1 transition-transform">

                        <span>Explore Scalar Docs</span>

                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>

                    </div>

                </a>


                <!-- Swagger UI Card -->
                <a
                    href="https://app.swaggerhub.com/apis-docs/wisework-4e6/hr-api-documentation/1.0.0?view=uiDocs#/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group relative bg-white rounded-2xl p-6 lg:p-8 border border-slate-200/80 shadow-sm hover:shadow-xl hover:border-blue-500/50 transition-all duration-300 flex flex-col justify-between overflow-hidden"
                >

                    <div class="flex items-start justify-between mb-6">

                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">

                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>

                        </div>

                        <span class="inline-flex items-center text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg">
                            OpenAPI 3.0
                        </span>

                    </div>


                    <div>

                        <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-blue-600 transition-colors">
                            SwaggerHub Console
                        </h3>

                        <p class="text-slate-500 text-sm leading-relaxed mb-6">
                            Standardized OpenAPI UI interface detailing all schema models, responses, and security schemes.
                        </p>

                    </div>


                    <div class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 group-hover:translate-x-1 transition-transform">

                        <span>View SwaggerHub Console</span>

                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>

                    </div>

                </a>

            </div>

        </div>

    </main>


    <!-- Footer -->
    <footer class="w-full bg-white border-t border-slate-200/80 py-6">

        <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">

            <p>
                &copy; {{ date('Y') }} WiseWork System. All rights reserved.
            </p>

            <p class="font-medium text-slate-400">
                Laravel v{{ app()->version() }}
            </p>

        </div>

    </footer>

</body>

</html>
