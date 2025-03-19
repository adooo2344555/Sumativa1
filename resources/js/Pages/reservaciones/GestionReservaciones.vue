<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import { ref, onMounted, computed } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import Swal from 'sweetalert2';

const toast = useToast();

const reservaciones = ref([]);
const reservacion = ref(null);
const selectedReservacion = ref(null);
const estado = ref('P'); // Cambiar el estado inicial a 'P'
const showDetailsDialog = ref(false);

const estados = {
    'P' :  'Reservaciones Pendientes',
    'C' :  'Reservaciones Confirmadas',
    'A' :  'Reservaciones Canceladas'
};

const filters = ref({
    'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
});

const filteredReservaciones = computed(() => {
    return reservaciones.value?.filter(reserva => reserva?.estado === estado.value);
});

const fetchReservaciones = async () => {
    try {
        const response = await axios.get('/api/reservaciones');
        reservaciones.value = response.data;
        console.log(response.data);
    } catch (error) {
        console.error("Error al obtener reservaciones:", error);
    }
};

const hideDialog = () => {
    showDetailsDialog.value = false;
};

const viewDetails = (data) => {
    reservacion.value = { ...data };
    showDetailsDialog.value = true;
};

const changeReservacion = async (reservacion, nuevoEstado) => {
    const estadoTexto = nuevoEstado === 'C' ? 'Confirmada' : 'Cancelada';

    const result = await Swal.fire({
        title: `¿Seguro(a) que desea ${estadoTexto} la Reservacion No: ${reservacion.id}?`,
        text: "Esta acción no se puede revertir",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí',
        cancelButtonText: 'No',
    });

    if (result.isConfirmed) {
        try {
            const response = await axios.put(`/api/reservaciones/${reservacion.id}`, { estado: nuevoEstado });

            if (response.status === 202) {
                const index = reservaciones.value.findIndex(r => r.id === reservacion.id);
                if (index !== -1) {
                    reservaciones.value[index].estado = nuevoEstado;

                    // Si se despachó, actualiza la fecha de despacho
                    if (nuevoEstado === 'C') {
                        reservaciones.value[index].fecha_despacho = new Date().toISOString().split('T')[0];
                    }
                }

                toast.add({
                    severity: 'success',
                    summary: 'Éxito',
                    detail: `Reservacion ${estadoTexto} correctamente`,
                    life: 3000
                });
            }
        } catch (error) {
            console.error("Error al cambiar estado de la reservacion:", error);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || "No se pudo cambiar el estado de la reservación",
                life: 3000
            });
        }
    }
};


onMounted(fetchReservaciones);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <div>
                    <div class="card">
                        <Toolbar class="mb-4">
                            <template #start>
                                <div class="form-check form-check-inline" v-for="(label, key) in estados" :key="key">
                                    <input class="form-check-input mr-2" v-model="estado" type="radio" name="reservacion.estado" :id="key" :value="key">
                                    <label class="form-check-label mr-4" :for="key">{{ label }}</label>
                                </div>
                            </template>
                            <template #end>
                                <IconField iconPosition="left">
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                </IconField>
                            </template>
                        </Toolbar>

                        <DataTable ref="dt" :value="filteredReservaciones" v-model:selection="selectedReservacion" dataKey="id"
                            :paginator="true" :rows="10" :filters="filters"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25]"
                            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reservaciones"
                            size="small"
                            tableStyle="min-width: 50rem">
                            
                            <Column field="id" header="Reservacion No" sortable></Column>
                            <Column field="fecha" header="Fecha Reservacion"></Column>
                            <Column field="fecha_despacho" header="Fecha Despacho"></Column>

                            <Column field="user.name" header="Cliente"></Column>
                            <Column :exportable="false">
                                <template #body="{ data }">
                                    <Button icon="pi pi-list" outlined rounded class="mr-2" severity="info"
                                        @click="viewDetails(data)"
                                        v-tooltip="{ value: 'Ver Detalle', showDelay: 1000, hideDelay: 300 }" />
                                    <Button icon="pi pi-check" outlined rounded class="mr-2"
                                        v-if="data.estado === 'P'" @click="changeReservacion(data, 'C')"
                                        v-tooltip="{ value: 'Completar Reservacion', showDelay: 1000, hideDelay: 300 }" />
                                    <Button icon="pi pi-trash" outlined rounded severity="danger"
                                        v-if="data.estado === 'P'" @click="changeReservacion(data, 'A')"
                                        v-tooltip="{ value: 'Cancelar Reservacion', showDelay: 1000, hideDelay: 300 }" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Modal para mostrar detalles de la reservacion -->
                    <Dialog v-model:visible="showDetailsDialog" class="p-fluid" :style="{ width: '650px' }" header="Detalle de Reservacion" :modal="true">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4 text-sm font-medium text-gray-700">
                                <div class="inline-flex items-center">
                                    <span class="block text-gray-500">Reservacion: </span>
                                    <span class="font-semibold">{{ reservacion?.id }}</span>
                                </div>
                                <div class="inline-flex items-center">
                                    <span class="block text-gray-500">Fecha Orden: </span>
                                    <span class="font-semibold">{{ reservacion?.fecha }}</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm font-medium text-gray-700">
                                <div class="inline-flex items-center">
                                    <span class="block text-gray-500">Cliente: </span>
                                    <span class="font-semibold">{{ reservacion?.user?.name }}</span>
                                </div>
                                <div class="inline-flex items-center">
                                    <span class="block text-gray-500">Estado: </span>
                                    <span class="font-semibold">{{ reservacion?.estado === 'P' ? 'Pendiente' : reservacion?.estado === 'C' ? 'Confirmada' : 'Cancelada' }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse rounded-lg shadow-md overflow-hidden">
                                    <thead>
                                        <tr class="bg-gray-800 text-white text-left">
                                            <th class="px-4 py-2">Cantidad</th>
                                            <th class="px-4 py-2">Subtotal</th>
                                            <th class="px-4 py-2">Reservacion</th>
                                            <th class="px-4 py-2">Producto</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in reservacion?.detalle_reservaciones" :key="item.id" class="hover:bg-gray-100">
                                            <td class="px-4 py-2 text-center">{{ item.cantidad }}</td>
                                            <td class="px-4 py-2 text-center font-semibold">${{ item.subtotal }}</td>
                                            <td class="px-4 py-2">{{ reservacion?.id }}</td>
                                            <td class="px-4 py-2">{{ item.producto.nombre }}, {{ item.producto.descripcion }}, material: {{ item.producto.material }}</td>
                                        </tr>
                                        <tr class="bg-gray-200 font-semibold">
                                            <td colspan="3" class="px-4 py-2 text-right">Total de la Reservacion</td>
                                            <td class="px-4 py-2 text-center text-lg">${{ reservacion?.total }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <template #footer>
                            <Button label="Cerrar" icon="pi pi-times" text @click="hideDialog" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg" />
                        </template>
                    </Dialog>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>