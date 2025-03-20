<script setup>
import { ref} from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

//variables
const fechaInicio = ref("");
const fechaFinal = ref("");
const estado = ref("P")
const error = ref('');

//funcion para enviar paramentros al reporte 
const generarReporte = () => {
    if(!fechaInicio.value || !fechaFinal.value || !estado.value){
        error.value = "Seleccione todos los parámentros para generar el reporte";
        return;
    }
    error.value = '';

    const url = `/reportes/reservaciones?fechaInicio=${fechaInicio.value}&fechaFinal=${fechaFinal.value}&estado=${estado.value}`;
    window.open(url, "_blank");
}

</script>

<template>
    <Head title="Reporte de reservaciones" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Reporte de Reservaciones
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <h2 class="text-xl font-bold mb-4 text-black">Generar Reporte de Reservaciones</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="fechaInicio">Fecha Inicio</label>
                        <input type="date" id="fechaInicio" v-model="fechaInicio" class="w-full p-2 border rounded-md  text-black" />
                    </div>
                    <div>
                        <label for="fechaFinal">Fecha Final</label>
                        <input type="date" id="fechaFinal" v-model="fechaFinal" class="w-full p-2 border rounded-md  text-black" />
                    </div>

                    <div>
                        <label for="estado" class="block font-medium">Seleccione Estado</label>
                        <select id="estado" v-model="estado" class="w-full p-2 border rounded-md  text-black">
                            <option value="P">Pendientes</option>
                            <option value="C">Confirmadas</option>
                            <option value="A">Canceladas</option>
                        </select>
                        <div v-if="error" class="mt-4">
                            <p class="text-red-600 text-sm mt-2">{{ error }}</p>
                        </div>
                    </div>

                    <div>
                        <button @click="generarReporte" class="px-6 mt-4 bg-yellow-600 hover:bg-yellow-700 py-3 rounded-lg shadow-md text-white">
                            Generar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
