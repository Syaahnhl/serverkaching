<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Kaching POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 p-6">

    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">🍔 Kelola Produk</h1>
                <p class="text-gray-500 text-sm mt-1">Tambah, edit, dan hapus menu restoranmu</p>
            </div>
            <a href="/" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                ← Kembali ke Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 shadow-sm flex justify-between items-center">
                <span>{{ session('success') }}</span>
                <span class="text-2xl cursor-pointer" onclick="this.parentElement.style.display='none';">&times;</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 sticky top-6">
                    <h2 class="text-lg font-bold mb-4 text-gray-800 flex items-center gap-2">
                        <span class="bg-blue-100 text-blue-600 p-1 rounded">➕</span> Tambah Menu Baru
                    </h2>
                    
                    <form action="{{ route('menus.store') }}" method="POST" class="space-y-4" enctype="multipart/form-data">
                        @csrf
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Menu</label>
                            <input type="text" name="name" placeholder="Contoh: Ayam Bakar Madu" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Kategori</label>
                            <select name="category" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white" required>
                                <option value="Makanan">🍛 Makanan</option>
                                <option value="Minuman">🥤 Minuman</option>
                                <option value="Camilan">🍟 Camilan</option>
                                <option value="Lainnya">📦 Lainnya</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Harga Jual</label>
                                <input type="number" name="price" placeholder="Rp 0" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Modal (HPP)</label>
                                <input type="number" name="cost_price" placeholder="Rp 0" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Stok Awal</label>
                                <input type="number" name="stock" value="100" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                            </div>
                            <div class="pt-5">
                                <label class="flex items-center gap-2 cursor-pointer select-none bg-gray-50 p-2 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
                                    <input type="checkbox" name="has_variant" value="1" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Punya Varian?</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Foto Menu (Opsional)</label>
                            <input type="file" name="image" accept="image/*" class="w-full border border-gray-300 p-2 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG (Max 2MB)</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi (Opsional)</label>
                            <textarea name="description" rows="2" placeholder="Penjelasan singkat..." class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg transition transform active:scale-95">
                            Simpan Menu
                        </button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h2 class="font-bold text-gray-700">Daftar Menu ({{ $menus->count() }} Item)</h2>
                        <span class="text-xs text-gray-400 bg-white border px-2 py-1 rounded">Urut: Terbaru</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-gray-500 uppercase text-xs tracking-wider">
                                    <th class="py-3 px-6 font-semibold">Produk</th>
                                    <th class="py-3 px-6 text-right font-semibold">Harga</th>
                                    <th class="py-3 px-6 text-center font-semibold">Stok</th>
                                    <th class="py-3 px-6 text-center font-semibold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm">
                                @foreach($menus as $menu)
                                <tr class="border-b border-gray-100 hover:bg-blue-50 transition group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                                                @if($menu->image_url)
                                                    <img src="{{ asset('storage/' . $menu->image_url) }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-lg">🍔</div>
                                                @endif
                                            </div>

                                            <div class="flex flex-col">
                                                <span class="font-bold text-gray-800 text-base group-hover:text-blue-600 transition">{{ $menu->name }}</span>
                                                <div class="flex gap-2 mt-1">
                                                    <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">{{ $menu->category }}</span>
                                                    @if($menu->has_variant)
                                                        <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full border border-yellow-200">Varian</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="font-bold text-gray-800">Rp {{ number_format($menu->price, 0, ',', '.') }}</div>
                                        <div class="text-xs text-green-600">Modal: {{ number_format($menu->cost_price, 0, ',', '.') }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        @if($menu->stock == -1)
                                            <span class="text-2xl text-blue-500">∞</span>
                                        @else
                                            <span class="font-bold {{ $menu->stock < 10 ? 'text-red-500' : 'text-gray-700' }}">{{ $menu->stock }}</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <form action="{{ route('menus.destroy', $menu->id) }}" method="POST" onsubmit="return confirm('Yakin hapus menu ini?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 bg-white border border-red-200 text-red-500 hover:bg-red-50 hover:text-red-700 rounded-lg transition shadow-sm" title="Hapus">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>