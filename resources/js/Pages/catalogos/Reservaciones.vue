<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

import { ref, onMounted, computed } from "vue";
import { FilterMatchMode } from "@primevue/core/api";
import { useToast } from "primevue/usetoast";
import Button from "primevue/button";
import Toolbar from "primevue/toolbar";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Calendar from "primevue/calendar";
import Dropdown from "primevue/dropdown";
import axios from "axios";

onMounted(() => {
    fetchReservaciones();
    fetchUsers();
});

const toast = useToast();
const dt = ref();
const reservaciones = ref([]);
const dialog = ref(false);
const deleteReservacionDialog = ref(false);
const reservacion = ref({});
const selectedReservaciones = ref();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const submitted = ref(false);
const url = "http://127.0.0.1:8000/api/reservaciones";
const users = ref([]);
const estados = ref(["C", "P", "Ca"]);

const openNew = () => {
    reservacion.value = {};
    submitted.value = false;
    dialog.value = true;
};
const hideDialog = () => {
    dialog.value = false;
    submitted.value = false;
};
//método para obtener las reservaciones
const fetchReservaciones = async () => {
    try {
        const response = await axios.get(url);
        reservaciones.value = response.data;
    } catch (err) {
        console.error("Error al obtener las Reservaciones", err);
    }
};
const fetchUsers = async () => {
    try {
        const response = await axios.get("http://127.0.0.1:8000/api/users");
        users.value = response.data;
    } catch (err) {
        console.error("Error al obtener los usuarios", err);
    }
};
const saveOrUpdate = async () => {
    submitted.value = true;

    if (reservacion?.value.fecha && reservacion?.value.estado?.trim() && reservacion?.value.user_id) {
        if (reservacion.value.id) {
            //se va actualizar el registro
            try {
                const response = await axios.put(
                    `${url}/${reservacion.value.id}`,
                    reservacion.value
                );
                if (response.status === 202) {
                    const { reservacion: nuevaReservacion, message } = response.data;
                    //actualizamos el dato en el arreglo
                    const index = reservaciones.value.findIndex(
                        (m) => m.id === nuevaReservacion.id
                    );
                    if (index !== -1) {
                        reservaciones.value[index] = nuevaReservacion;
                    }
                    toast.add({
                        severity: "success",
                        summary: "Successful",
                        detail: message,
                        life: 3000,
                    });
                    dialog.value = false;
                    reservacion.value = {};
                }
            } catch (err) {
                console.error(err);
            }
        } else {
            //proceso para guardar nuevo registro
            try {
                const response = await axios.post(url, reservacion.value);
                if (response.status === 201) {
                    const { reservacion: nuevaReservacion, message } = response.data;
                    reservaciones.value.unshift(nuevaReservacion);
                    toast.add({
                        severity: "success",
                        summary: "Successful",
                        detail: message,
                        life: 3000,
                    });
                    dialog.value = false;
                    reservacion.value = {};
                }
            } catch (err) {
                if (err.response.status === 409) {
                    toast.add({
                        severity: "warn",
                        summary: "Advertencia",
                        detail: `${err.response.data.message}, Ya existe esta reservacion`,
                        life: 3000,
                    });
                }
                console.error(err);
            }
        }
    }
};
const editReservacion = (item) => {
    reservacion.value = { ...item };
    dialog.value = true;
};
const confirmDeleteReservacion = (m) => {
    reservacion.value = m;
    deleteReservacionDialog.value = true;
};
const deleteReservacion = async () => {
    try {
        const response = await axios.delete(`${url}/${reservacion.value.id}`);
        if (response.status === 200) {
            const { message } = response.data;
            reservaciones.value = reservaciones.value.filter(
                (val) => val.id !== reservacion.value.id);
            toast.add({
                severity: "success",
                summary: "Successful",
                detail: message,
                life: 3000,});
        }
    } catch (err) {
        if (err.response.status === 500 || err.response.status === 409) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: err.response.data.message,
                life: 3000,
            });
        }
        console.error("Error al eliminar la Reservacion", err);
    }
    deleteReservacionDialog.value = false;
    reservacion.value = {};
};

const exportCSV = () => {
    dt.value.exportCSV();
};

/*propiedades computables para hacer dinámico el titulo del formulario y 
titulo del boton */
const dialogTitle = computed(() =>
    reservacion.value.id ? "Edición de Reservaciones" : "Registro de Reservaciones"
);
const btnTitle = computed(() => (reservacion.value.id ? "Actualizar" : "Guardar"));
</script>

<template>
    <Head title="Reservaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                CRUD de Reservaciones
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div>
                    <div class="card">
                        <Toolbar class="mb-6">
                            <template #start>
                                <Button
                                    label="Nueva Reservación"
                                    icon="pi pi-plus"
                                    class="mr-2"
                                    @click="openNew"
                                />
                            </template>

                            <template #end>
                                <Button
                                    label="Exportar"
                                    icon="pi pi-upload"
                                    severity="secondary"
                                    @click="exportCSV($event)"
                                />
                            </template>
                        </Toolbar>

                        <DataTable
                            ref="dt"
                            v-model:selection="selectedReservaciones"
                            :value="reservaciones"
                            dataKey="id"
                            :paginator="true"
                            :rows="10"
                            :filters="filters"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25]"
                            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} reservaciones"
                        >
                            <template #header>
                                <div
                                    class="flex flex-wrap gap-2 items-center justify-between"
                                >
                                    <h4 class="m-0">Gestión de Reservaciones</h4>
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            v-model="filters['global'].value"
                                            placeholder="Search..."
                                        />
                                    </IconField>
                                </div>
                            </template>
                            <Column
                                field="id"
                                header="ID"
                                sortable
                                style="min-width: 8rem"
                            ></Column>
                            <Column
                                field="fecha"
                                header="Fecha"
                                sortable
                                style="min-width: 16rem"
                            ></Column>
                            <Column
                                field="estado"
                                header="Estado"
                                sortable
                                style="min-width: 8rem"
                            ></Column>
                            <Column
                                field="user.name"
                                header="Cliente"
                                sortable
                                style="min-width: 8rem"
                            ></Column>
                            <Column
                                :exportable="false"
                                style="min-width: 12rem"
                            >
                                <template #body="slotProps">
                                    <Button
                                        icon="pi pi-pencil"
                                        outlined
                                        rounded
                                        class="mr-2"
                                        @click="editReservacion(slotProps.data)"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        outlined
                                        rounded
                                        severity="danger"
                                        @click="
                                            confirmDeleteReservacion(slotProps.data)
                                        "
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <Dialog
                        v-model:visible="dialog"
                        :style="{ width: '450px' }"
                        :header="dialogTitle"
                        :modal="true"
                    >
                        <div class="flex flex-col gap-6">
                            <div>
                                <label for="fecha" class="block font-bold mb-3">Fecha</label>
                                <Calendar
                                    id="fecha"
                                    v-model="reservacion.fecha"
                                    required="true"
                                    :showTime="true"
                                    :invalid="submitted && !reservacion.fecha"
                                    fluid
                                />
                                <small
                                    v-if="submitted && !reservacion.fecha"
                                    class="text-red-500"
                                >Fecha es requerida.</small>
                            </div>
                            <div>
                                <label for="estado" class="block font-bold mb-3">Estado</label>
                                <Dropdown
                                    id="estado"
                                    v-model="reservacion.estado"
                                    :options="estados"
                                    required="true"
                                    :invalid="submitted && !reservacion.estado"
                                    fluid
                                />
                                <small
                                    v-if="submitted && !reservacion.estado"
                                    class="text-red-500"
                                >Estado es requerido.</small>
                            </div>
                            <div>
                                <label for="user_id" class="block font-bold mb-3">Cliente</label>
                                <Dropdown
                                    id="user_id"
                                    v-model="reservacion.user_id"
                                    :options="users"
                                    optionLabel="name"
                                    optionValue="id"
                                    required="true"
                                    :invalid="submitted && !reservacion.user.nombre"
                                    fluid
                                />
                                <small
                                    v-if="submitted && !reservacion.user.nombre"
                                    class="text-red-500"
                                >Cliente es requerido.</small>
                            </div>
                        </div>

                        <template #footer>
                            <Button
                                label="Cancelar"
                                icon="pi pi-times"
                                text
                                @click="hideDialog"
                            />
                            <Button
                                :label="btnTitle"
                                icon="pi pi-check"
                                @click="saveOrUpdate"
                            />
                        </template>
                    </Dialog>

                    <Dialog
                        v-model:visible="deleteReservacionDialog"
                        :style="{ width: '450px' }"
                        header="Confirmación"
                        :modal="true"
                    >
                        <div class="flex items-center gap-4">
                            <i class="pi pi-exclamation-triangle !text-3xl" />
                            <span v-if="reservacion"
                                >Seguro(a) de eliminar la reservacion
                                <b>{{ reservacion.fecha }}</b
                                >?</span
                            >
                        </div>
                        <template #footer>
                            <Button
                                label="No"
                                icon="pi pi-times"
                                text
                                @click="deleteReservacionDialog = false"
                            />
                            <Button
                                label="Si"
                                icon="pi pi-check"
                                @click="deleteReservacion"
                            />
                        </template>
                    </Dialog>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
