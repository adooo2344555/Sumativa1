<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { Head } from '@inertiajs/vue3';
  
    import { ref, onMounted,computed } from 'vue';
    import { FilterMatchMode } from '@primevue/core/api';
    import { useToast } from 'primevue/usetoast';
    import axios from 'axios';
    

    onMounted(() => {
        fetchCategorias();     
    });

        const toast = useToast();
        const dt = ref();
        const categorias = ref([]);
        const dialog = ref(false);
        const deleteCategoriaDialog = ref(false);
        const categoria = ref({});
        const selectedCategorias = ref();
        const filters = ref({
            'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
        });
        const submitted = ref(false);
        const url = 'http://127.0.0.1:8000/api/categorias';    
       
        const openNew = () => {
            categoria.value = {};
            submitted.value = false;
            dialog.value = true;
        };
        const hideDialog = () => {
            dialog.value = false;
            submitted.value = false;
        };
        //método para obtener las categorias
        const fetchCategorias = async () => {
            try{
                const response = await axios.get(url);
                categorias.value = response.data;
              
            }catch(err){
                console.error('Error al obtener las Categorias', err);
            }
        }
        const saveOrUpdate = async () => {
        submitted.value = true;
        if (categoria?.value.nombre?.trim()) {
            if (categoria.value.id) {
                // Se va a actualizar el registro
                try {
                    const response = await axios.put(`${url}/${categoria.value.id}`, categoria.value);
                    if (response.status===202) {
                        const { categoria: nuevaCategoria, message } = response.data;
                        //buscamos el indice del elmento en el array
                        const index = categorias.value.findIndex(m => m.id === nuevaCategoria.id);
                        if (index !== -1){categorias.value[index] = nuevaCategoria;
                        }
                        toast.add({ severity: 'success', summary: 'Successful',
                        detail: message, life:3000});
                        dialog.value = false;
                        categoria.value = {};
                    }
                } catch (err) {
                    console.error('Error al actualizar la categoria', err);
                }
            } else {
                // Proceso para nuevo registro
                try {
                    const response = await axios.post(url, categoria.value);
                    if (response.status === 201) {
                        const { categoria: nuevaCategoria, message } = response.data;
                        categorias.value.unshift(nuevaCategoria);
                        toast.add({ severity: 'success', summary: 'Successful', 
                        detail: message, life: 3000 });
                        dialog.value = false;
                        categoria.value = {};
                    }
                } catch (err) {
                    if (err.response && err.response.status === 409) {
                        toast.add({ severity: 'warn', summary: 'Advertencia',
                        detail: `${err.response.data.message}, Ya existe esta categoria`, life: 3000 });
                    } else {
                        console.error('Error al crear la cateogoria', err);
                    }
                }
            }            
        }
    };
    const editCategoria = (item ) => {
            
        categoria.value = {...item};
        dialog.value = true;
    };
    const confirmDeleteCategoria = (m) => {
        categoria.value = m;
        deleteCategoriaDialog.value = true;
    };
    const deleteCategoria = async () => {
        try{
            const response = await axios.delete(`${url}/${categoria.value.id}`);
            if (response.status === 200) {
                categorias.value = categorias.value.filter(val => val.id !== categoria.value.id);
                deleteCategoriaDialog.value = false;
                categoria.value = {};
                toast.add({severity:'success', summary: 'Successful', detail: message, life: 3000});
            }

        }catch(err){
            if(err.status === 500 || err.status === 409){
                toast.add({severity:'error', summary: 'Error',
                detail: err.response.data.error , life: 3000});   
            }else{
                console.error('Error al eliminar la Categoria', err);
            }
        }
        deleteCategoriaDialog.value = false;
        categoria.value = {};
    };
       
    const exportCSV = () => {
        dt.value.exportCSV();
    };

    /*propiedades computables para hacer dinámico el titulo del formulario y 
    titulo del boton */
    const dialogTitle = computed(() =>
        categoria.value.id ? "Edición de Categorias" : "Registro de Categorias"
    );
    const btnTitle = computed(() => (categoria.value.id ? "Actualizar" : "Guardar"));
</script>

<template>
    <Head title="Categorias" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                CRUD de Categorias
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
               
                <div>
                    <div class="card">
                        <Toolbar class="mb-6">
                            <template #start>
                                <Button label="Nuevo" icon="pi pi-plus" class="mr-2" @click="openNew" />
                            </template>

                            <template #end>
                                <Button label="Exportar" icon="pi pi-upload" severity="secondary" @click="exportCSV($event)" />
                            </template>
                        </Toolbar>

                        <DataTable
                            ref="dt"
                            v-model:selection="selectedCategorias"
                            :value="categorias"
                            dataKey="id"
                            :paginator="true"
                            :rows="10"
                            :filters="filters"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25]"
                            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} categorias"
                        >
                            <template #header>
                                <div class="flex flex-wrap gap-2 items-center justify-between">
                                    <h4 class="m-0">Gestión de Categorias</h4>
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                                    </IconField>
                                </div>
                            </template>
                            <Column field="nombre" header="Categoria" sortable style="min-width: 16rem"></Column>
                            <Column :exportable="false" style="min-width: 12rem">
                                <template #body="slotProps">
                                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editCategoria(slotProps.data)" />
                                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteCategoria(slotProps.data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <Dialog v-model:visible="dialog" :style="{ width: '450px' }" :header="dialogTitle" :modal="true">
                        <div class="flex flex-col gap-6">
                            <div>
                                <label for="nombre" class="block font-bold mb-3">Nombre Categoria</label>
                                <InputText id="nombre" v-model.trim="categoria.nombre" required="true" autofocus :invalid="submitted && !categoria.nombre" fluid />
                                <small v-if="submitted && !categoria.nombre" class="text-red-500">Nombre es requerido.</small>
                            </div>
                        </div>

                        <template #footer>
                            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
                            <Button :label="btnTitle" icon="pi pi-check" @click="saveOrUpdate" />
                        </template>
                    </Dialog>

                    <Dialog v-model:visible="deleteCategoriaDialog" :style="{ width: '450px' }" header="Confirmación" :modal="true">
                        <div class="flex items-center gap-4">
                            <i class="pi pi-exclamation-triangle !text-3xl" />
                            <span v-if="categoria"
                                >Seguro(a) de eliminar la categoria <b>{{ categoria.nombre }}</b
                                >?</span
                            >
                        </div>
                        <template #footer>
                            <Button label="No" icon="pi pi-times" text @click="deleteCategoriaDialog = false" />
                            <Button label="Si" icon="pi pi-check" @click="deleteCategoria" />
                        </template>
                    </Dialog>
                
                </div> 
            </div>
        </div>

    </AuthenticatedLayout>
</template>
