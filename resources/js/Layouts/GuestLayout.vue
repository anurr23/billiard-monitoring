<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="bb-auth-wrapper">
        <div class="bb-auth-card">
            <!-- Logo -->
            <div class="text-center mb-4">
                <Link href="/" class="text-decoration-none d-inline-flex align-items-center gap-2">
                    <div class="bb-logo-icon" style="width: 48px; height: 48px; font-size: 1.3rem;">B</div>
                    <span class="fw-bolder fs-4 text-white">Billiar billing</span>
                </Link>
            </div>
            <slot />
        </div>
    </div>
</template>
