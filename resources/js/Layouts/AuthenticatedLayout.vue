<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { usePage, Link } from "@inertiajs/vue3"; 
import { ToastService } from 'primevue';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faBars, faHome, faBox, faClipboardList, faFileAlt, faLayerGroup, faTimes, faUserCircle, faSignOutAlt }
 from '@fortawesome/free-solid-svg-icons';
 import axios from 'axios';

 const page = usePage(); 
const user = page.props.auth.user;
const isSidebarOpen = ref(false);

//funciones para la logica de el componente
const logout = async () => {
    try{
        await axios.post('/logout');
        window.location.href = '/login';
    }catch(err){
        console.error('Error al cerrar la sesion', err);
    }
};
</script>

<template>
    <Toast/>
  <div class="h-screen flex flex-col">
     <!--navbar-->
     <header class="bg-white text-black shadow-md fixed top-0 w-full z-50">
         <div class="px-6 py-4 flex justify-between items-center">
            <!--boton para ocultar/mostrar el sidebar-->
            <button @click="isSidebarOpen = !isSidebarOpen" class="text-yellow-600 focus:outline-none">
                <FontAwesomeIcon :icon="faBars"/>
            </button>
            <div class="text-xl font-bold text-yellow-700">Admin Dashboard</div>
            <!--Datos de la seccion-->
            <div class="flex items-center space-x-4">
                <FontAwesomeIcon :icon="faUserCircle" class="text-2xl"/>
                <div>
                    <p class="text-sm font-semibold">{{ user.name }}</p>
                </div>
                <button @click="logout" class="text-black hover:text-gray-700">
                    <FontAwesomeIcon :icon="faSignOutAlt"/>
                </button>
            </div>
         </div>
    </header>
    <!--Inicio div para sidebar y contenido dinamico-->
    <div class="flex mt-16">
        <aside :class="{
        '-translate-x-full md:translate-x-0' :!isSidebarOpen,
        'translate-x-0' : isSidebarOpen
        }"
        class="fixed md:relative top-0 left-0 h-[calc(100vh-64px)] w-64 bg-gray-100
         text-black transform transition-transform duration-300 ease-in-out shadow-lg z-40">
        <div class="px-4 text-xl font-bold flex justify-between items-center">
            <span>MENU</span>
            <button @click="isSidebarOpen = false" class="md:hidden text-gray-300 hover:text-black">
                <FontAwesomeIcon :icon="faTimes"/>
            </button>
        </div>
        <nav class="mt-4">
            <ul>
                <li class="px-6 py-3 hover:bg-yellow-500 flex items-center">
                    <a :href="route('dashboard')" class="flex items-center w-full text-black">
                        <FontAwesomeIcon :icon="faHome" class="mr-3"/>
                        Inicio
                    </a>
                </li>
                 <li class="px-6 py-3 hover:bg-yellow-500 flex items-center">
                    <a :href="route('categorias')" class="flex items-center w-full text-black">
                        <FontAwesomeIcon :icon="faLayerGroup" class="mr-3"/>
                        Categorias
                    </a>
                </li> 
                <li class="px-6 py-3 hover:bg-yellow-500 flex items-center">
                    <Link :href="route('productos')" class="flex items-center w-full text-black">
                        <FontAwesomeIcon :icon="faBox" class="mr-3"/>
                        Productos
                    </Link>
                </li>
                <li class="px-6 py-3 hover:bg-yellow-500 flex items-center">
                    <Link :href="route('reservaciones')" class="flex items-center w-full text-black">
                        <FontAwesomeIcon :icon="faBox" class="mr-3"/>
                        Reservaciones
                    </Link>
                </li>
                <li class="px-6 py-3 hover:bg-yellow-500 flex items-center">
                    <Link :href="route('reservaciones.rango')" class="flex items-center w-full text-black">
                        <FontAwesomeIcon :icon="faFileAlt" class="mr-3"/>
                        Reportes
                    </Link>
                </li>
            </ul>
        </nav>
     </aside>
     <!--Div para el contenido dinamico-->
        <main class="flex-1 p-6 overflow-auto bg-gray-50">
            <slot/>

        </main>
    </div>
    <!--Fin del div para sidebar y contenido dinamico-->
  </div>
  <footer class="bg-gray-600 text-white text-center py-3 shadow-md bottom-0 left-0 w-full">
    <p>&copy; {{ new Date().getFullYear()}}  Todos los derechos reservados</p>
  </footer>
</template>