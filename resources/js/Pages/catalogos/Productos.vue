<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import { ref, onMounted, computed } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';
import { FileUpload, InputNumber } from 'primevue';
import { Swiper, SwiperSlide } from "swiper/vue"; //para el slide
import "swiper/css";
import "swiper/css/navigation";
import { Navigation } from "swiper/modules";

//import { ProductService } from '@/service/ProductService';

onMounted(() => {
    fetchProductos();
    fetchCategorias();
});

const toast = useToast();
const dt = ref();
const productos = ref([]);
const categorias = ref([]);
const dialog = ref(false);//permite mostrar/ocultar el modal para agregar/editar productos
const deleteProductDialog = ref(false);
const showImagesDialog = ref(false);
const producto = ref({});
const selectedProducts = ref();
const imagenes = ref([]);//arreglo de imagenes para el producto
const imagesPreview = ref([]); // arrar para hacer un preview de las imagenes
const FileUploadRef = ref(null);//referencia al componente FileUpload

const filters = ref({
    'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const submitted = ref(false);
const url = '/api/productos';

const statuses = ref([
    { label: 'INSTOCK', value: 'instock' },
    { label: 'LOWSTOCK', value: 'lowstock' },
    { label: 'OUTOFSTOCK', value: 'outofstock' }
]);

const formatCurrency = (value) => {
    if (value)
        return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    return;
};
const openNew = () => {
    producto.value = {};
    submitted.value = false;
    dialog.value = true;
};
const hideDialog = () => {
    dialog.value = false;
    submitted.value = false;
    clearFiles();
};
//FUNCIONES PARA HACER PETICIONES A ALA API
const fetchProductos = async () => {
    try {
        const response = await axios.get(url);
        productos.value = response.data;
    } catch (err) {
        console.error("Error al obtener los productos", err);
    }
};


//funcion para obtener las categorias
const fetchCategorias = async () => {
    try {
        const response = await axios.get('http://127.0.0.1:8000/api/categorias');
        categorias.value = response.data;
        console.log("Categorias fetched:", categorias.value); // Debugging: Log the fetched categories
    } catch (err) {
        console.error("Error al obtener los productos", err);
    }
};

const saveOrUpdate = async () => {
    submitted.value = true;

    if (producto?.value.nombre?.trim()) {
        const formData = new FormData();
        formData.append('producto', JSON.stringify(producto.value)); // Convertimos el producto a JSON
        // Agregar las imágenes al formData
        imagenes.value.forEach((img) => {
            formData.append("imagenes[]", img);
        });

        if (producto.value.id) {
            // Se va actualizar el producto
            try {
                const response = await axios.post(`${url}/${producto.value.id}`, formData, {
                    headers: { "Content-Type": "multipart/form-data" }
                });
                if (response.status === 200) {
                    const { producto: nuevoProducto, message } = response.data;
                    // Actualizamos el dato en el arreglo
                    const index = productos.value.findIndex(m => m.id === nuevoProducto.id);
                    if (index !== -1) {
                        productos.value[index] = nuevoProducto;
                    }
                    toast.add({ severity: 'success', summary: 'Successful', detail: message, life: 3000 });
                    dialog.value = false;
                    producto.value = {};
                    imagenes.value = [];
                }
            } catch (err) {
                if (err.response.status === 409) {
                    toast.add({
                        severity: 'warn', summary: 'Advertencia',
                        detail: `${err.response.data.message}, Ya existe este producto`, life: 3000
                    });
                }
                console.error(err);
            }
        } else {
            // Se va agregar un nuevo producto
            try {
                const response = await axios.post(url, formData, {
                    headers: { "Content-Type": "multipart/form-data" }
                });
                if (response.status === 201) {
                    const { producto: nuevoProducto, message } = response.data;
                    productos.value.unshift(nuevoProducto.original);
                    toast.add({
                        severity: 'success', summary: 'Registrado',
                        detail: message, life: 3000
                    });
                    dialog.value = false;
                    producto.value = {};
                    imagenes.value = [];
                }
            } catch (err) {
                if (err.response && err.response.status === 409) {
                    const message = err.response.data;
                    toast.add({
                        severity: 'warn', summary: 'Advertencia',
                        detail: message, life: 3000
                    });
                } else if (err.response && err.response.status === 500) {
                    const { error } = err.response.data;
                    toast.add({
                        severity: 'error', summary: 'Error',
                        detail: error, life: 3000
                    });
                } else {
                    console.error(err);
                }
            }
        }
    }
};




//funcion para editar un producto
const editProduct = (prod) => {
    producto.value = { ...prod };
    dialog.value = true;
};
const confirmDeleteProduct = (prod) => {
    producto.value = { ...prod };
    deleteProductDialog.value = true;
};
const viewImages = (product) => {
    producto.value = { ...product };
    showImagesDialog.value = true;
/*     console.log(producto.value.imagenes); // Verifica que las imágenes estén cargadas
 */
};

const deleteProduct = async () => {
    try {
        const response = await axios.delete(`${url}/${producto.value.id}`);
        if(response.status===205){
            const { message } = response.data;
            productos.value = productos.value.filter(val => val.id !== producto.value.id);
            toast.add({ severity: 'success', summary: 'Producto eliminado', detail: message , life: 3000 });

        }
    } catch (err) {
        if(err.status=== 500 || err.status === 409){
            const { error } = err.response.data;
            toast.add({ severity: 'error', summary: 'Error', 
            detail: error , life: 3000 });
        }else{
            console.error("Error al eliminar un producto ", err);
        }
    }
    deleteProductDialog.value = false;
    producto.value = {};
};


const findIndexById = (id) => {
    let index = -1;
    for (let i = 0; i < productos.value.length; i++) {
        if (productos.value[i].id === id) {
            index = i;
            break;
        }
    }

    return index;
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'INSTOCK':
            return 'success';

        case 'LOWSTOCK':
            return 'warn';

        case 'OUTOFSTOCK':
            return 'danger';

        default:
            return null;
    }
};

//funcion para seleccionar las imagenes

const onImageSelect = (event) => {
    imagenes.value = []; //seteamos el arreglo de imagenes
    imagesPreview.value = [];

    for (const file of event.files) {
        imagenes.value.push(file); //se guardan las imagenes que se enviaran
        //para generar el preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagesPreview.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
    }
};
//funcion para eliminar una imagen del preview
const removeImage = (index) => {
    imagenes.value.splice(index, 1);
    imagesPreview.value.splice(index,1);
};
//funcion para setear las imagenes del arreglo
const onFileClear = () => {
    imagenes.value = [];
};

//funcion para limpiar el formulario o los archivos del fileupload
const clearFiles = () => {
    if (FileUploadRef.value) {
        FileUploadRef.value.clear();//limpia los archivos del fileupload
    }
    imagenes.value = [];//limpia el arreglo de imagenes
    imagesPreview.value = [];
};

//propiedades computables para hacer dinamico el titulo del formulario y titulo del boton

const dialogTitle = computed(() =>
    producto.value.id ? "Edición de Productos" : "Registro de Productos"
);
const btnTitle = computed(() => (producto.value.id ? "Actualizar" : "Guardar"));
</script>

<template>

    <Head title="Productos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Productos
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

                <!--Inicio template de productos - primevue -->
                <div>
                    <div class="card">
                        <Toolbar class="mb-6">
                            <template #start>
                                <Button label="Nuevo Producto" icon="pi pi-plus" class="mr-2" @click="openNew" />
                            </template>

                            <template #end>
                                <FileUpload mode="basic" accept="image/*" :maxFileSize="1000000" label="Import"
                                    customUpload chooseLabel="Import" class="mr-2" auto
                                    :chooseButtonProps="{ severity: 'secondary' }" />
                                <Button label="Export" icon="pi pi-upload" severity="secondary"
                                    @click="exportCSV($event)" />
                            </template>
                        </Toolbar>

                        <DataTable ref="dt" v-model:selection="selectedProducts" :value="productos" dataKey="id"
                            :paginator="true" :rows="10" :filters="filters"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            :rowsPerPageOptions="[5, 10, 25, 50]"
                            currentPageReportTemplate="Mostrando del {first} al {last} de {totalRecords} productos">
                            <template #header>
                                <div class="flex flex-wrap gap-2 items-center justify-between">
                                    <h4 class="m-0">Gestion de Productos</h4>
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                                    </IconField>
                                </div>
                            </template>

                            <Column field="nombre" header="Producto" sortable style="min-width: 16rem"></Column>
                            <Column field="descripcion" header="Descripcion" sortable style="min-width: 16rem"></Column>

                            <Column field="precio" header="Precio" sortable style="min-width: 8rem">
                                <template #body="slotProps">
                                    {{ formatCurrency(slotProps.data.precio) }}
                                </template>
                            </Column>
                            <Column field="material" header="Material" sortable style="min-width: 16rem"></Column>

                            <Column field="categoria.nombre" header="Categoria" sortable style="min-width: 10rem">
                            </Column>
                            <!--
                            <Column field="inventoryStatus" header="Status" sortable style="min-width: 12rem">
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.inventoryStatus" :severity="getStatusLabel(slotProps.data.inventoryStatus)" />
                                </template>
                            </Column>-->
                            <Column :exportable="false" style="min-width: 12rem">
                                <template #body="slotProps">
                                    <Button icon="pi pi-images" outlined rounded class="mr-2" severity="info" @click="viewImages(slotProps.data)" />
                                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editProduct(slotProps.data)" />
                                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteProduct(slotProps.data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                    <!--Modal para guadar o actualizar un producto-->

                    <Dialog v-model:visible="dialog" :style="{ width: '500px' }" :header="dialogTitle" :modal="true" class="p-fluid">
                        <div class="p-4">
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="nombre" class="block font-bold mb-2">Nombre</label>
                                    <InputText id="nombre" v-model.trim="producto.nombre" required="true" autofocus
                                        :invalid="submitted && !producto.nombre" class="w-full" />
                                    <small v-if="submitted && !producto.nombre" class="text-red-500">Nombre es requerido.</small>
                                </div>
                                <div>
                                    <label for="descripcion" class="block font-bold mb-2">Descripcion</label>
                                    <Textarea id="descripcion" v-model="producto.descripcion" required="true" rows="3" class="w-full" />
                                    <small class="p-error text-red-500" v-if="submitted && !producto.descripcion">Descripcion es requerido.</small>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="precio" class="block font-bold mb-2">Precio</label>
                                        <InputNumber id="precio" v-model="producto.precio" mode="currency" currency="USD" locale="en-US" class="w-full" />
                                        <small class="p-error text-red-500" v-if="submitted && !producto.precio">Precio es requerido.</small>
                                    </div>
                                    <div>
                                        <label for="material" class="block font-bold mb-2">Material</label>
                                        <InputText id="material" v-model="producto.material" required="true" class="w-full" />
                                        <small class="p-error text-red-500" v-if="submitted && !producto.material">Material es requerido.</small>
                                    </div>
                                </div>
                                <div>
                                    <label for="categoria" class="block font-bold mb-2">Categoria</label>
                                    <Select v-model="producto.categoria" :options="categorias" option-label="nombre" class="w-full" />
                                    <small v-if="submitted && !producto.categoria" class="text-red-500">Seleccione una categoria</small>
                                </div>
                                <div>
                                    <label class="block font-bold mb-2">Imágenes</label>
                                    <FileUpload ref="FileUploadRef" mode="basic" accept="image/*" customUpload multiple
                                        @select="onImageSelect" choose-label="Seleccionar Imágenes" class="w-full" />
                                </div>
                                <div v-if="imagesPreview.length" class="grid grid-cols-3 gap-3 mt-3">
                                    <div v-for="(img, index) in imagesPreview" :key="index" class="relative group">
                                        <img :src="img" class="w-full h-24 object-cover rounded-md shadow">
                                        <Button icon="pi pi-times" text class="absolute top-0 right-0 p-1 text-white rounded-full bg-red-500 opacity-0 group-hover:opacity-100" @click="removeImage(index)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <template #footer>
                            <Button label="Cancelar" icon="pi pi-times" text @click="hideDialog" />
                            <Button :label="btnTitle" icon="pi pi-check" @click="saveOrUpdate" />
                        </template>
                    </Dialog>
                    <!-- Dialog para mostrar las imagenes deel producto -->
                    <Dialog v-model:visible="showImagesDialog" header="Imagenes del producto"
                        :style="{ width: '550px' }" class="p-fluid">
                        <Swiper :modules="[Navigation]" navigation class="h-40">
                            <SwiperSlide v-for="img in producto.imagenes" :key="img.id">
                                <img :src="`images/products/${img.nombre}`" class="w-full h-40 object-contain" />
                            </SwiperSlide>
                        </Swiper>
                    </Dialog>

                    
                    <Dialog v-model:visible="deleteProductDialog" :style="{ width: '450px' }" header="Confirmacion" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="producto"
                    >Segur@ que quieres eliminar el producto <b>{{ producto.nombre }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteProductDialog = false" />
                <Button label="Si" icon="pi pi-check" @click="deleteProduct" />
            </template>
        </Dialog>
        
      <!--   <Dialog v-model:visible="deleteProductsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle !text-3xl" />
                <span v-if="product">Are you sure you want to delete the selected products?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteProductsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedProducts" />
            </template>
        </Dialog> -->
                </div>
                <!--Fin template de productos - primevue -->

            </div>
        </div>
    </AuthenticatedLayout>
</template>