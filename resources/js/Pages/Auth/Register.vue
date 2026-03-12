<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registrarse" />

        <div class="mb-6 text-center">
            <h2 class="font-heading font-black text-2xl tracking-tighter text-white uppercase leading-none">
                CREA TU<span class="text-neon-blue"> CUENTA</span>
            </h2>
            <p class="text-gray-500 text-xs mt-1.5 font-medium italic">Únete a la élite de los pronósticos</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="name" value="Nombre de Usuario" class="text-gray-300 font-bold mb-1.5 ml-1 text-xs uppercase tracking-widest" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full bg-white/5 border-white/10 text-white focus:border-neon-blue focus:ring-neon-blue/20 rounded-xl transition-all duration-300 placeholder:text-gray-600"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Tu nick de batalla"
                />

                <InputError class="mt-2 text-xs font-bold text-red-400" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Correo Electrónico" class="text-gray-300 font-bold mb-1.5 ml-1 text-xs uppercase tracking-widest" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-white/5 border-white/10 text-white focus:border-neon-blue focus:ring-neon-blue/20 rounded-xl transition-all duration-300 placeholder:text-gray-600"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="ejemplo@correo.com"
                />

                <InputError class="mt-2 text-xs font-bold text-red-400" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" class="text-gray-300 font-bold mb-1.5 ml-1 text-xs uppercase tracking-widest" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full bg-white/5 border-white/10 text-white focus:border-neon-blue focus:ring-neon-blue/20 rounded-xl transition-all duration-300 placeholder:text-gray-600"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                />

                <InputError class="mt-2 text-xs font-bold text-red-400" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar Contraseña"
                    class="text-gray-300 font-bold mb-1.5 ml-1 text-xs uppercase tracking-widest"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full bg-white/5 border-white/10 text-white focus:border-neon-blue focus:ring-neon-blue/20 rounded-xl transition-all duration-300 placeholder:text-gray-600"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repite tu contraseña"
                />

                <InputError
                    class="mt-2 text-xs font-bold text-red-400"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="pt-4">
                <PrimaryButton
                    class="w-full justify-center py-4 bg-transparent border-2 border-neon-blue text-white font-black text-sm uppercase tracking-[0.2em] rounded-xl hover:bg-neon-blue hover:text-black transition-all duration-500 shadow-[0_0_20px_rgba(0,212,255,0.2)] hover:shadow-[0_0_35px_rgba(0,212,255,0.5)] active:scale-95"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    REGISTRARSE AHORA
                </PrimaryButton>
            </div>

            <div class="flex flex-col items-center mt-6 gap-6">
                <div class="flex items-center gap-4 w-full">
                    <div class="h-px flex-1 bg-white/5"></div>
                    <span class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">o regístrate con</span>
                    <div class="h-px flex-1 bg-white/5"></div>
                </div>

                <button
                    type="button"
                    class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-white/5 border border-white/10 rounded-xl text-white text-xs font-bold uppercase tracking-widest hover:bg-white/10 hover:border-white/20 transition-all duration-300 group"
                >
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.47 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </button>

                <p class="text-xs text-gray-500 font-medium uppercase tracking-widest">
                    ¿Ya tienes una cuenta? 
                    <Link :href="route('login')" class="text-neon-blue hover:underline font-black ml-1">Inicia Sesión</Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
