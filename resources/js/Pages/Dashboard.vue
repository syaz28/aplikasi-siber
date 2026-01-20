<script setup>
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <SidebarLayout title="Dashboard">
        <div class="max-w-7xl mx-auto">
            <!-- Welcome Card -->
            <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-tactical-accent rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-navy">Selamat Datang di SIBER JATENG</h2>
                        <p class="text-gray-500">Sistem Informasi Laporan Kejahatan Siber - Polda Jawa Tengah</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Entry Laporan Card -->
                <Link href="/laporan/create" class="block">
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6 hover:shadow-lg hover:border-tactical-accent transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-tactical-accent/10 rounded-lg flex items-center justify-center group-hover:bg-tactical-accent transition-colors">
                                <svg class="w-6 h-6 text-tactical-accent group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-navy group-hover:text-tactical-accent transition-colors">Entry Laporan Baru</h3>
                                <p class="text-sm text-gray-500">Buat laporan kejahatan siber baru</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 ml-auto group-hover:text-tactical-accent group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </Link>

                <!-- Arsip Laporan Card -->
                <Link href="/laporan" class="block">
                    <div class="bg-white rounded-xl shadow-tactical border border-tactical-border p-6 hover:shadow-lg hover:border-tactical-accent transition-all group">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-tactical-accent/10 rounded-lg flex items-center justify-center group-hover:bg-tactical-accent transition-colors">
                                <svg class="w-6 h-6 text-tactical-accent group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-navy group-hover:text-tactical-accent transition-colors">Arsip Laporan</h3>
                                <p class="text-sm text-gray-500">Lihat semua laporan yang sudah dibuat</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 ml-auto group-hover:text-tactical-accent group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </SidebarLayout>
</template>
