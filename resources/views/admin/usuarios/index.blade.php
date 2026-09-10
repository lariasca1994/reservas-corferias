@extends('layout.panel')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Usuarios</h1>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-principal">Nueva cuenta</a>
    </div>

    <div class="tarjeta">
        <div class="table-responsive">
            <table class="table tabla-panel align-middle mb-0">
                <thead>
                    <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr>
                            <td class="fw-semibold">
                                {{ $usuario->name }}
                                @if ($usuario->is(auth()->user()))
                                    <span class="etiqueta etiqueta--neutra ms-1">Tú</span>
                                @endif
                            </td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->etiquetaRol() }}</td>
                            <td>
                                <span class="etiqueta {{ $usuario->activo ? 'etiqueta--confirmada' : 'etiqueta--cancelada' }}">
                                    {{ $usuario->activo ? 'Activa' : 'Desactivada' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                @unless ($usuario->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar la cuenta de {{ $usuario->name }}?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin cuentas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $usuarios->links() }}</div>
@endsection
