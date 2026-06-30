<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DetalleVentaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoFacilWebHookController;
use App\Http\Controllers\PagoProveedorController;
use App\Http\Controllers\PlanPagoController;
use App\Http\Controllers\PrivilegioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ReporteController::class, 'index'])
        ->middleware('privilegio:Reportes,leer')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Configuración de empresa
    Route::get('/empresa', [EmpresaController::class, 'index'])
        ->middleware('privilegio:Empresa,leer')
        ->name('empresa.index');
    Route::put('/empresa/{empresa}/nombre', [EmpresaController::class, 'nombre'])
        ->middleware('privilegio:Empresa,modificar')
        ->name('empresa.nombre');
    Route::put('/empresa/{empresa}/direccion', [EmpresaController::class, 'direccion'])
        ->middleware('privilegio:Empresa,modificar')
        ->name('empresa.direccion');
    Route::put('/empresa/{empresa}/correo', [EmpresaController::class, 'correo'])
        ->middleware('privilegio:Empresa,modificar')
        ->name('empresa.correo');
    Route::put('/empresa/{empresa}/telefono', [EmpresaController::class, 'telefono'])
        ->middleware('privilegio:Empresa,modificar')
        ->name('empresa.telefono');
    Route::post('/empresa/{empresa}/logo', [EmpresaController::class, 'logo'])
        ->middleware('privilegio:Empresa,modificar')
        ->name('empresa.logo');
    Route::delete('/empresa/{empresa}/logo', [EmpresaController::class, 'eliminarLogo'])
        ->middleware('privilegio:Empresa,modificar')
        ->name('empresa.logo.eliminar');

    // Clientes
    Route::get('/cliente', [ClienteController::class, 'index'])
        ->middleware('privilegio:Cliente,leer')
        ->name('cliente.index');
    Route::post('/cliente', [ClienteController::class, 'store'])
        ->middleware('privilegio:Cliente,agregar')
        ->name('cliente.store');
    Route::put('/cliente/{cliente}', [ClienteController::class, 'update'])
        ->middleware('privilegio:Cliente,modificar')
        ->name('cliente.update');
    Route::delete('/cliente/{cliente}', [ClienteController::class, 'destroy'])
        ->middleware('privilegio:Cliente,borrar')
        ->name('cliente.destroy');

    // Pagos de clientes
    Route::get('/pago', [PagoController::class, 'index'])
        ->middleware('privilegio:Pago,leer')
        ->name('pago.index');
    Route::post('/pago', [PagoController::class, 'store'])
        ->middleware('privilegio:Pago,agregar')
        ->name('pago.store');
    Route::put('/pago/{pago}', [PagoController::class, 'update'])
        ->middleware('privilegio:Pago,modificar')
        ->name('pago.update');
    Route::delete('/pago/{pago}', [PagoController::class, 'destroy'])
        ->middleware('privilegio:Pago,borrar')
        ->name('pago.destroy');


    Route::get('/metodos-pago', [MetodoPagoController::class, 'index'])
        ->middleware('privilegio:MetodoPago,leer')
        ->name('metodos-pago.index');
    Route::post('/metodos-pago', [MetodoPagoController::class, 'store'])
        ->middleware('privilegio:MetodoPago,agregar')
        ->name('metodos-pago.store');
    Route::put('/metodos-pago/{metodo_pago}', [MetodoPagoController::class, 'update'])
        ->middleware('privilegio:MetodoPago,modificar')
        ->name('metodos-pago.update');
    Route::delete('/metodos-pago/{metodo_pago}', [MetodoPagoController::class, 'destroy'])
        ->middleware('privilegio:MetodoPago,borrar')
        ->name('metodos-pago.destroy');

    // Privilegios
    Route::get('/privilegio', [PrivilegioController::class, 'index'])
        ->middleware('privilegio:Privilegio,leer')
        ->name('privilegio.index');
    Route::post('/privilegio', [PrivilegioController::class, 'store'])
        ->middleware('privilegio:Privilegio,agregar')
        ->name('privilegio.store');
    Route::put('/privilegio/{privilegio}', [PrivilegioController::class, 'updateOne'])
        ->middleware('privilegio:Privilegio,modificar')
        ->name('privilegio.update');
    Route::delete('/privilegio/{privilegio}', [PrivilegioController::class, 'destroy'])
        ->middleware('privilegio:Privilegio,borrar')
        ->name('privilegio.destroy');
    Route::post('/privilegios/asignar/{rol}', [PrivilegioController::class, 'asignar'])
        ->middleware('privilegio:Privilegio,modificar')
        ->name('privilegios.asignar');
    Route::put('/privilegios/{rol}', [PrivilegioController::class, 'updateByRol'])
        ->middleware('privilegio:Privilegio,modificar')
        ->name('privilegios.update');
    Route::delete('/privilegios/rol/{rol}', [PrivilegioController::class, 'destroyByRol'])
        ->middleware('privilegio:Privilegio,borrar')
        ->name('privilegios.destroyByRol');

    // Productos y categorías
    Route::get('/producto', [ProductoController::class, 'index'])
        ->middleware('privilegio:Producto,leer')
        ->name('producto.index');
    Route::post('/producto', [ProductoController::class, 'store'])
        ->middleware('privilegio:Producto,agregar')
        ->name('producto.store');
    Route::put('/producto/{producto}', [ProductoController::class, 'update'])
        ->middleware('privilegio:Producto,modificar')
        ->name('producto.update');
    Route::delete('/producto/{producto}', [ProductoController::class, 'destroy'])
        ->middleware('privilegio:Producto,borrar')
        ->name('producto.destroy');

    Route::get('/categoria', [CategoriaController::class, 'index'])
        ->middleware('privilegio:Categoria,leer')
        ->name('categoria.index');
    Route::post('/categoria', [CategoriaController::class, 'store'])
        ->middleware('privilegio:Categoria,agregar')
        ->name('categoria.store');
    Route::put('/categoria/{categoria}', [CategoriaController::class, 'update'])
        ->middleware('privilegio:Categoria,modificar')
        ->name('categoria.update');
    Route::delete('/categoria/{categoria}', [CategoriaController::class, 'destroy'])
        ->middleware('privilegio:Categoria,borrar')
        ->name('categoria.destroy');

    // Roles y usuarios
    Route::get('/rol', [RoleController::class, 'index'])
        ->middleware('privilegio:Rol,leer')
        ->name('rol.index');
    Route::post('/rol', [RoleController::class, 'store'])
        ->middleware('privilegio:Rol,agregar')
        ->name('rol.store');
    Route::put('/rol/{rol}', [RoleController::class, 'update'])
        ->middleware('privilegio:Rol,modificar')
        ->name('rol.update');
    Route::delete('/rol/{rol}', [RoleController::class, 'destroy'])
        ->middleware('privilegio:Rol,borrar')
        ->name('rol.destroy');

    Route::get('/usuario', [UsuarioController::class, 'index'])
        ->middleware('privilegio:Usuario,leer')
        ->name('usuario.index');
    Route::post('/usuario', [UsuarioController::class, 'store'])
        ->middleware('privilegio:Usuario,agregar')
        ->name('usuario.store');
    Route::put('/usuario/{usuario}', [UsuarioController::class, 'update'])
        ->middleware('privilegio:Usuario,modificar')
        ->name('usuario.update');
    Route::delete('/usuario/{usuario}', [UsuarioController::class, 'destroy'])
        ->middleware('privilegio:Usuario,borrar')
        ->name('usuario.destroy');

    // Ventas, planes y detalle de ventas
    Route::get('/venta', [VentaController::class, 'index'])
        ->middleware('privilegio:Venta,leer')
        ->name('venta.index');
    Route::post('/venta', [VentaController::class, 'store'])
        ->middleware('privilegio:Venta,agregar')
        ->name('venta.store');
    Route::get('/venta/{venta}', [VentaController::class, 'show'])
        ->middleware('privilegio:Venta,leer')
        ->name('venta.show');
    Route::put('/venta/{venta}', [VentaController::class, 'update'])
        ->middleware('privilegio:Venta,modificar')
        ->name('venta.update');
    Route::delete('/venta/{venta}', [VentaController::class, 'destroy'])
        ->middleware('privilegio:Venta,borrar')
        ->name('venta.destroy');

    Route::post('detalle_ventas/calcular', [DetalleVentaController::class, 'calcularTotal'])
        ->middleware('privilegio:Venta,leer')
        ->name('detalle_ventas.calcular');
    Route::get('ventas/{venta}/detalles', [VentaController::class, 'detalles'])
        ->middleware('privilegio:Venta,leer')
        ->name('ventas.detalles');

    Route::get('planes', [PlanPagoController::class, 'index'])
        ->middleware('privilegio:PlanPago,leer')
        ->name('planes.index');
    Route::delete('planes/{plan}', [PlanPagoController::class, 'destroy'])
        ->middleware('privilegio:PlanPago,borrar')
        ->name('planes.destroy');
    Route::post('/ventas/{venta}/pagos', [PagoController::class, 'store'])
        ->middleware('privilegio:Pago,agregar')
        ->name('pagos.store');
    Route::post('/ventas/{venta}/plan', [PlanPagoController::class, 'guardarPlan'])
        ->middleware('privilegio:PlanPago,agregar')
        ->name('planes.store');
    Route::post('/ventas/{venta}/pagar-cuotas', [PlanPagoController::class, 'pagarCuota'])
        ->middleware('privilegio:Pago,agregar')
        ->name('planes.pagarCuota');
    Route::get('/ventas/{cuota}/pagar-cuotas', [PlanPagoController::class, 'pagarCuota2'])
        ->middleware('privilegio:Pago,agregar')
        ->name('planes.pagarCuota2');
    Route::get('/ventas/{venta}/pagar-qr', [PlanPagoController::class, 'pagarQR'])
        ->middleware('privilegio:Pago,agregar')
        ->name('planes.pagarQR');
    Route::get('/pagofacil/venta/{venta}/estado', [PlanPagoController::class, 'consultarEstadoPagoFacilVenta'])
        ->middleware('privilegio:Pago,agregar')
        ->name('pagofacil.estado.venta');
    Route::get('/pagofacil/cuota/{cuota}/estado', [PlanPagoController::class, 'consultarEstadoPagoFacilCuota'])
        ->middleware('privilegio:Pago,agregar')
        ->name('pagofacil.estado.cuota');

    // Reportes y auditoría
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('privilegio:Reportes,leer')
        ->name('reportes.index');
    Route::get('/reportes-buscar', [ReporteController::class, 'buscador'])
        ->middleware('privilegio:Reportes,leer')
        ->name('reportes.buscar');
    Route::get('/estadisticas', [ReporteController::class, 'estadisticas'])
        ->middleware('privilegio:Reportes,leer')
        ->name('estadisticas.index');
    Route::get('/reportes-financieros', [ReporteController::class, 'financiero'])
        ->middleware('privilegio:Reportes,leer')
        ->name('reportes.financiero');
    Route::get('/reportes-financieros/exportar-csv', [ReporteController::class, 'exportarFinancieroCsv'])
        ->middleware('privilegio:Reportes,leer')
        ->name('reportes.financiero.exportar');
    Route::get('/auditoria', [AuditoriaController::class, 'index'])
        ->middleware('privilegio:Auditoria,leer')
        ->name('auditoria.index');

    // Inventario
    Route::get('/inventario', [InventarioController::class, 'index'])
        ->middleware('privilegio:Inventario,leer')
        ->name('inventario.index');
    Route::post('/inventario', [InventarioController::class, 'store'])
        ->middleware('privilegio:Inventario,agregar')
        ->name('inventario.store');
    Route::put('/inventario/{inventario}', [InventarioController::class, 'update'])
        ->middleware('privilegio:Inventario,modificar')
        ->name('inventario.update');
    Route::delete('/inventario/{inventario}', [InventarioController::class, 'destroy'])
        ->middleware('privilegio:Inventario,borrar')
        ->name('inventario.destroy');

    // Proveedores, compras, solicitudes y pagos a proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index'])
        ->middleware('privilegio:Proveedor,leer')
        ->name('proveedores.index');
    Route::post('/proveedores', [ProveedorController::class, 'store'])
        ->middleware('privilegio:Proveedor,agregar')
        ->name('proveedores.store');
    Route::get('/proveedores/{proveedor}', [ProveedorController::class, 'show'])
        ->middleware('privilegio:Proveedor,leer')
        ->name('proveedores.show');
    Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->middleware('privilegio:Proveedor,modificar')
        ->name('proveedores.update');
    Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'destroy'])
        ->middleware('privilegio:Proveedor,borrar')
        ->name('proveedores.destroy');

    Route::get('/compras', [CompraController::class, 'index'])
        ->middleware('privilegio:Compra,leer')
        ->name('compras.index');
    Route::post('/compras', [CompraController::class, 'store'])
        ->middleware('privilegio:Compra,agregar')
        ->name('compras.store');
    Route::get('/compras/{compra}', [CompraController::class, 'show'])
        ->middleware('privilegio:Compra,leer')
        ->name('compras.show');
    Route::put('/compras/{compra}/aprobar', [CompraController::class, 'aprobar'])
        ->middleware('privilegio:Compra,modificar')
        ->name('compras.aprobar');
    Route::put('/compras/{compra}/rechazar', [CompraController::class, 'rechazar'])
        ->middleware('privilegio:Compra,modificar')
        ->name('compras.rechazar');
    Route::delete('/compras/{compra}', [CompraController::class, 'destroy'])
        ->middleware('privilegio:Compra,borrar')
        ->name('compras.destroy');

    Route::get('/solicitudes', [SolicitudController::class, 'index'])
        ->middleware('privilegio:Solicitud,leer')
        ->name('solicitudes.index');
    Route::post('/solicitudes', [SolicitudController::class, 'store'])
        ->middleware('privilegio:Solicitud,agregar')
        ->name('solicitudes.store');
    Route::get('/solicitudes/{solicitud}', [SolicitudController::class, 'show'])
        ->middleware('privilegio:Solicitud,leer')
        ->name('solicitudes.show');
    Route::put('/solicitudes/{solicitud}', [SolicitudController::class, 'update'])
        ->middleware('privilegio:Solicitud,modificar')
        ->name('solicitudes.update');
    Route::delete('/solicitudes/{solicitud}', [SolicitudController::class, 'destroy'])
        ->middleware('privilegio:Solicitud,borrar')
        ->name('solicitudes.destroy');

    Route::get('/pagos-proveedor', [PagoProveedorController::class, 'index'])
        ->middleware('privilegio:PagoProveedor,leer')
        ->name('pagos-proveedor.index');
    Route::post('/pagos-proveedor', [PagoProveedorController::class, 'store'])
        ->middleware('privilegio:PagoProveedor,agregar')
        ->name('pagos-proveedor.store');
    Route::delete('/pagos-proveedor/{pagos_proveedor}', [PagoProveedorController::class, 'destroy'])
        ->middleware('privilegio:PagoProveedor,borrar')
        ->name('pagos-proveedor.destroy');

    Route::get('/dashboardvue', [ReporteController::class, 'indexVue'])
        ->middleware('privilegio:Reportes,leer')
        ->name('dashboardvue');

    Route::get('/cargar-estilo/{estilo}', [ReporteController::class, 'cargarEstilo'])->name('cargarEstilo');
    Route::post('/confirmar-pago', [PagoController::class, 'confirmarPago'])
        ->middleware('privilegio:Pago,agregar')
        ->name('pago.confirmar');
});

Route::get('/unauthorized', [EmpresaController::class, 'intruso'])->name('intruso');
Route::post('/pagofacil/callback', [PagoFacilWebHookController::class, 'callback'])->name('pagofacil.callback');

require __DIR__ . '/auth.php';
