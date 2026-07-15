<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Profil Saya" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-person-circle me-2" style="color: #6366f1;"></i>Profil Saya</h1>
        </template>

        <div class="row g-4">
            <!-- Profile Card -->
            <div class="col-12">
                <div class="bb-card">
                    <div class="bb-card-body d-flex align-items-center gap-4 flex-wrap">
                        <div v-if="user.photo_url">
                            <img :src="user.photo_url" :alt="user.name" style="width: 80px; height: 80px; border-radius: 16px; object-fit: cover;" />
                        </div>
                        <div v-else style="width: 80px; height: 80px; border-radius: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1.8rem; flex-shrink: 0;">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">{{ user.name }}</h4>
                            <div class="text-secondary small mb-2">@{{ user.username }}</div>
                            <span class="bb-badge" :style="user.role === 'admin' ? 'background: rgba(139,92,246,0.1); color: #8b5cf6;' : 'background: rgba(56,189,248,0.1); color: #38bdf8;'">
                                <i class="bi" :class="user.role === 'admin' ? 'bi-shield-lock' : 'bi-person'"></i> {{ user.role }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Profile -->
            <div class="col-12 col-lg-7">
                <div class="bb-card">
                    <div class="bb-card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-success"></i>Informasi Profil</h6>
                    </div>
                    <div class="bb-card-body">
                        <UpdateProfileInformationForm />
                    </div>
                </div>
            </div>

            <!-- Update Password -->
            <div class="col-12 col-lg-5">
                <div class="bb-card">
                    <div class="bb-card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-lock me-2" style="color: #f59e0b;"></i>Ubah Password</h6>
                    </div>
                    <div class="bb-card-body">
                        <UpdatePasswordForm />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
