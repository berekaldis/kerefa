@extends('layouts.app')

@section('title', 'POS Order Terminal')

@push('styles')
<style>
    /* Responsive POS Layout */
    .pos-layout {
        min-height: calc(100vh - 130px);
    }

    @media (min-width: 992px) {
        .pos-layout {
            height: calc(100vh - 130px);
            overflow: hidden;
        }
        .pos-grid-scroll {
            height: calc(100vh - 225px);
            overflow-y: auto;
        }
        .pos-docket-scroll {
            flex-grow: 1;
            overflow-y: auto;
            max-height: calc(100vh - 460px);
        }
    }

    @media (max-width: 991.98px) {
        .pos-grid-scroll {
            max-height: none;
            overflow-y: visible;
        }
        .pos-docket-scroll {
            max-height: 380px;
            overflow-y: auto;
        }
    }

    /* Category Buttons */
    .pos-cat-pill {
        white-space: nowrap;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.45rem 1.15rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #475569;
    }
    .pos-cat-pill:hover {
        background: #f1f5f9;
        color: #0f172a;
        transform: translateY(-1px);
    }
    .pos-cat-pill.active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
    }

    /* Product Card */
    .pos-product-card {
        cursor: pointer;
        user-select: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .pos-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 22px -4px rgba(15, 23, 42, 0.08);
        border-color: var(--kerefa-accent);
    }
    .pos-product-card:active {
        transform: scale(0.97);
    }

    /* Department Pills */
    .badge-dept-kitchen {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .badge-dept-bar {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    /* Docket Styles */
    .pos-docket-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-direction: column;
    }

    /* Floating Cart Button for Mobile */
    .floating-cart-bar {
        position: fixed;
        bottom: 16px;
        left: 16px;
        right: 16px;
        z-index: 1030;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.35);
        border-radius: 16px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border: 1px solid rgba(255, 255, 255, 0.15);
        animation: floatUp 0.3s ease;
    }
    @keyframes floatUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="pos-layout">
    <!-- Mobile Segmented View Switcher (Only visible on screens < 992px) -->
    <div class="d-lg-none mb-3">
        <div class="btn-group w-100 shadow-sm rounded-pill p-1 bg-white border" role="group">
            <button type="button" class="btn btn-sm rounded-pill active fw-bold py-2" id="mobileTabMenu" onclick="switchMobileView('menu')">
                <i class="bi bi-grid-fill me-1 text-warning"></i> Menu Items
            </button>
            <button type="button" class="btn btn-sm rounded-pill fw-bold py-2" id="mobileTabDocket" onclick="switchMobileView('docket')">
                <i class="bi bi-receipt me-1 text-primary"></i> Order Ticket <span class="badge bg-danger rounded-pill ms-1" id="mobileCartBadge">0</span>
            </button>
        </div>
    </div>

    <div class="row g-3 h-100">
        <!-- ==========================================
             LEFT COLUMN: MENU CATALOG, SEARCH, FILTERS
             ========================================== -->
        <div class="col-lg-7 col-xl-8 d-flex flex-column" id="menuCatalogCol">
            <!-- Header Filter & Action Bar -->
            <div class="card p-3 mb-3 border-0 shadow-sm rounded-4">
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-sm-7 col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" id="menuSearch" class="form-control border-start-0 ps-0" placeholder="Search dishes, drinks, coffee (e.g. Tibs, Jebena, Spris)...">
                            <button class="btn btn-outline-secondary border-start-0 d-none" id="clearSearchBtn" type="button">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-sm-5 col-md-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('tables.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill d-flex align-items-center gap-1 px-3">
                            <i class="bi bi-grid-3x3 text-primary"></i> <span class="d-none d-sm-inline">Tables</span>
                        </a>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill d-flex align-items-center gap-1 px-3">
                            <i class="bi bi-clock-history text-success"></i> <span class="d-none d-sm-inline">Orders</span>
                        </a>
                    </div>
                </div>

                <!-- Categories Horizontal Scroll -->
                <div class="d-flex gap-2 overflow-x-auto pb-1" id="categoryTabs">
                    <button class="btn pos-cat-pill active" data-category="all">
                        <i class="bi bi-grid-fill me-1 text-warning"></i> All Products
                    </button>
                    @foreach($categories as $cat)
                        <button class="btn pos-cat-pill" data-category="{{ $cat->slug }}">
                            <i class="bi {{ $cat->icon ?? 'bi-tag' }} me-1"></i> {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Products Grid Area -->
            <div class="pos-grid-scroll pe-lg-1">
                <div class="row g-2 g-sm-3" id="productGrid">
                    @foreach($categories as $cat)
                        @foreach($cat->menuItems as $item)
                            <div class="col-6 col-sm-4 col-md-4 col-xl-3 product-item" data-category="{{ $cat->slug }}" data-name="{{ strtolower($item->name) }} {{ strtolower($item->code) }}">
                                <div class="pos-product-card p-3 h-100 d-flex flex-column justify-content-between"
                                     onclick="addToCart({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->price }}, '{{ $item->preparation_department }}')">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge {{ $item->preparation_department === 'bar' ? 'badge-dept-bar' : 'badge-dept-kitchen' }} text-uppercase rounded-pill px-2 py-1">
                                                <i class="bi {{ $item->preparation_department === 'bar' ? 'bi-cup-straw' : 'bi-fire' }} me-1"></i>
                                                {{ $item->preparation_department }}
                                            </span>
                                            <span class="font-mono text-muted small" style="font-size: 0.72rem;">{{ $item->code }}</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.92rem; line-height: 1.3;">{{ $item->name }}</h6>
                                        <p class="text-muted small mb-2 text-truncate-2" style="font-size: 0.75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $item->description }}
                                        </p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="font-mono fw-bold text-dark fs-6">{{ number_format($item->price, 2) }} <small class="text-muted" style="font-size: 0.68rem;">ETB</small></span>
                                        <span class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;">
                                            <i class="bi bi-plus-lg text-primary fw-bold"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        <!-- ==========================================
             RIGHT COLUMN: ORDER DOCKET / CART SUMMARY
             ========================================== -->
        <div class="col-lg-5 col-xl-4 h-100" id="orderDocketCol">
            <div class="pos-docket-card h-100">
                <!-- Docket Header -->
                <div class="p-3 border-bottom bg-light bg-opacity-75 rounded-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-dot"></span>
                            <h6 class="fw-bold mb-0 text-dark">Order Ticket</h6>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 small">
                            <i class="bi bi-person-fill me-1"></i> {{ auth()->user()->name }}
                        </span>
                    </div>

                    <!-- Order Type Selector Buttons -->
                    <div class="btn-group w-100 mb-2 shadow-sm rounded-3" role="group">
                        <input type="radio" class="btn-check" name="order_type" id="type_dine_in" value="dine_in" checked onchange="toggleOrderTypeFields()">
                        <label class="btn btn-outline-dark btn-sm fw-semibold py-1" for="type_dine_in"><i class="bi bi-cup-hot me-1"></i> Dine-In</label>

                        <input type="radio" class="btn-check" name="order_type" id="type_takeaway" value="takeaway" onchange="toggleOrderTypeFields()">
                        <label class="btn btn-outline-dark btn-sm fw-semibold py-1" for="type_takeaway"><i class="bi bi-bag me-1"></i> Takeaway</label>

                        <input type="radio" class="btn-check" name="order_type" id="type_delivery" value="delivery" onchange="toggleOrderTypeFields()">
                        <label class="btn btn-outline-dark btn-sm fw-semibold py-1" for="type_delivery"><i class="bi bi-bicycle me-1"></i> Delivery</label>
                    </div>

                    <!-- Dine-in Table Selection -->
                    <div id="tableSelectGroup">
                        <select id="restaurant_table_id" class="form-select form-select-sm rounded-3">
                            <option value="">Select Dining Floor Table...</option>
                            @foreach($diningAreas as $area)
                                <optgroup label="{{ $area->name }}">
                                    @foreach($area->tables as $t)
                                        <option value="{{ $t->id }}" {{ $t->status === 'occupied' ? 'class=text-danger' : '' }}>
                                            Table {{ $t->table_number }} (Cap: {{ $t->capacity }}) - {{ strtoupper($t->status) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer / Delivery Info Fields -->
                    <div id="deliveryFieldsGroup" class="d-none mt-2">
                        <div class="row g-1">
                            <div class="col-6">
                                <input type="text" id="customer_name" class="form-control form-control-sm rounded-3" placeholder="Guest Name">
                            </div>
                            <div class="col-6">
                                <input type="text" id="customer_phone" class="form-control form-control-sm rounded-3" placeholder="Phone Number">
                            </div>
                            <div class="col-12 mt-1">
                                <input type="text" id="delivery_address" class="form-control form-control-sm rounded-3" placeholder="Delivery Drop-off Address">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Docket Items List -->
                <div class="pos-docket-scroll p-3" id="docketItemsContainer">
                    <div class="text-center text-muted py-5" id="emptyCartNotice">
                        <div class="mb-2">
                            <i class="bi bi-cart3 fs-1 text-secondary opacity-50"></i>
                        </div>
                        <p class="mb-1 fw-semibold text-dark">Ticket is Empty</p>
                        <span class="text-muted small">Tap dishes or drinks to add them to this order.</span>
                    </div>
                    <div id="cartItemsList" class="d-flex flex-column gap-2"></div>
                </div>

                <!-- Docket Order Note -->
                <div class="px-3 pt-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-chat-left-dots"></i></span>
                        <input type="text" id="order_notes" class="form-control border-start-0 rounded-end-3" placeholder="Kitchen instructions (e.g. VIP, urgent, no onions)...">
                    </div>
                </div>

                <!-- Docket Summary & Action Footer -->
                <div class="p-3 border-top bg-light bg-opacity-75 rounded-bottom mt-2">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Items Subtotal:</span>
                        <span class="font-mono fw-semibold text-dark" id="docketSubtotal">0.00 ETB</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Service (10%) & Tax (15%):</span>
                        <span class="font-mono fw-semibold text-dark" id="docketTaxes">0.00 ETB</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center fw-bold fs-5 border-top pt-2 mb-3">
                        <span class="text-dark">Order Total:</span>
                        <span class="font-mono text-primary fs-4" id="docketTotal">0.00 ETB</span>
                    </div>

                    <!-- Dispatch to Kitchen/Bar Action (Strictly NO Customer Bill Generation!) -->
                    <button type="button" class="btn btn-kerefa w-100 py-2 fs-6 shadow rounded-pill" id="btnSendToKitchen" onclick="submitPosOrder()">
                        <i class="bi bi-send-fill me-2"></i> Send to Kitchen & Bar
                    </button>
                    <div class="text-center text-muted mt-2" style="font-size: 0.72rem;">
                        <i class="bi bi-shield-check text-success me-1"></i> Auto-routed to Kitchen KDS & Bar BDS
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Floating Cart Pill Bar (Only on mobile/tablets when items exist) -->
<div class="floating-cart-bar p-3 d-lg-none d-none" id="mobileFloatingBar" onclick="switchMobileView('docket')">
    <div class="d-flex justify-content-between align-items-center text-white">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark font-mono rounded-pill px-2 py-1 fs-6" id="floatingCartCount">0</span>
            <div>
                <div class="fw-bold small" id="floatingCartTotal">0.00 ETB</div>
                <div class="text-white-50" style="font-size: 0.68rem;">Tap to review ticket</div>
            </div>
        </div>
        <div class="btn btn-sm btn-kerefa rounded-pill px-3 py-1 font-mono fw-bold">
            Send Ticket <i class="bi bi-arrow-right ms-1"></i>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: ORDER DISPATCH SUCCESS & IRP 200D THERMAL PRINTER ACTION
     ========================================================================= -->
<div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-labelledby="orderSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="orderSuccessModalLabel">
                    <i class="bi bi-check-circle-fill"></i> Order Dispatched!
                </h5>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width: 70px; height: 70px;">
                    <i class="bi bi-send-check-fill fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark font-mono mb-1" id="modalOrderNumber">ORD-XXXXXXXX</h5>
                <p class="text-muted small mb-3" id="modalOrderMeta">Dine-In &bull; Table T1</p>

                <!-- Thermal Printer Box (IRP 200D) -->
                <div class="p-3 bg-light border rounded-3 mb-3 text-start">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                        <span class="fw-bold small text-dark"><i class="bi bi-printer-fill text-warning me-1"></i> Order Printer (Model: IRP 200D)</span>
                        <span class="badge bg-secondary font-mono" style="font-size: 0.65rem;">80mm Thermal</span>
                    </div>
                    <div class="d-grid gap-2" id="modalPrintButtonsContainer">
                        <!-- Populated dynamically: Kitchen KOT and Bar BOT buttons -->
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal" onclick="resetPos()">
                        <i class="bi bi-plus-lg me-1"></i> Next Order
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-list-check me-1"></i> View Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let cart = [];
    let lastCreatedOrder = null;

    // Mobile View Switching
    function switchMobileView(view) {
        const menuCol = document.getElementById('menuCatalogCol');
        const docketCol = document.getElementById('orderDocketCol');
        const tabMenu = document.getElementById('mobileTabMenu');
        const tabDocket = document.getElementById('mobileTabDocket');
        const floatingBar = document.getElementById('mobileFloatingBar');

        if (view === 'docket') {
            menuCol.classList.add('d-none');
            docketCol.classList.remove('d-none');
            tabMenu.classList.remove('active');
            tabDocket.classList.add('active');
            floatingBar.classList.add('d-none');
        } else {
            menuCol.classList.remove('d-none');
            docketCol.classList.add('d-none');
            tabMenu.classList.add('active');
            tabDocket.classList.remove('active');
            if (cart.length > 0) {
                floatingBar.classList.remove('d-none');
            }
        }
    }

    // Auto-adjust layout on window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            document.getElementById('menuCatalogCol').classList.remove('d-none');
            document.getElementById('orderDocketCol').classList.remove('d-none');
            document.getElementById('mobileFloatingBar').classList.add('d-none');
        } else {
            // Default to menu on mobile
            switchMobileView('menu');
        }
    });

    // Category Filter Pills
    document.querySelectorAll('.pos-cat-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.pos-cat-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const cat = this.getAttribute('data-category');
            document.querySelectorAll('.product-item').forEach(item => {
                if (cat === 'all' || item.getAttribute('data-category') === cat) {
                    item.classList.remove('d-none');
                } else {
                    item.classList.add('d-none');
                }
            });
        });
    });

    // Realtime Search & Clear Button
    const searchInput = document.getElementById('menuSearch');
    const clearBtn = document.getElementById('clearSearchBtn');

    searchInput?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        clearBtn.classList.toggle('d-none', q === '');

        document.querySelectorAll('.product-item').forEach(item => {
            const name = item.getAttribute('data-name');
            if (name.includes(q)) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    });

    clearBtn?.addEventListener('click', function () {
        searchInput.value = '';
        clearBtn.classList.add('d-none');
        document.querySelectorAll('.product-item').forEach(i => i.classList.remove('d-none'));
    });

    function toggleOrderTypeFields() {
        const orderType = document.querySelector('input[name="order_type"]:checked').value;
        const tableGroup = document.getElementById('tableSelectGroup');
        const deliveryGroup = document.getElementById('deliveryFieldsGroup');

        if (orderType === 'dine_in') {
            tableGroup.classList.remove('d-none');
            deliveryGroup.classList.add('d-none');
        } else {
            tableGroup.classList.add('d-none');
            deliveryGroup.classList.remove('d-none');
        }
    }

    function addToCart(itemId, name, price, dept) {
        const existing = cart.find(i => i.menu_item_id === itemId);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                menu_item_id: itemId,
                name: name,
                price: parseFloat(price),
                quantity: 1,
                department: dept,
                special_instructions: ''
            });
        }
        renderCart();
    }

    function updateQty(index, delta) {
        if (!cart[index]) return;
        cart[index].quantity += delta;
        if (cart[index].quantity <= 0) {
            cart.splice(index, 1);
        }
        renderCart();
    }

    function updateItemNote(index, note) {
        if (cart[index]) {
            cart[index].special_instructions = note;
        }
    }

    function renderCart() {
        const container = document.getElementById('cartItemsList');
        const emptyNotice = document.getElementById('emptyCartNotice');
        const mobileBadge = document.getElementById('mobileCartBadge');
        const floatingBar = document.getElementById('mobileFloatingBar');
        const floatingCount = document.getElementById('floatingCartCount');
        const floatingTotal = document.getElementById('floatingCartTotal');

        container.innerHTML = '';
        const totalItemsCount = cart.reduce((acc, curr) => acc + curr.quantity, 0);

        mobileBadge.textContent = totalItemsCount;
        floatingCount.textContent = totalItemsCount;

        if (cart.length === 0) {
            emptyNotice.classList.remove('d-none');
            document.getElementById('docketSubtotal').textContent = '0.00 ETB';
            document.getElementById('docketTaxes').textContent = '0.00 ETB';
            document.getElementById('docketTotal').textContent = '0.00 ETB';
            floatingBar.classList.add('d-none');
            return;
        }

        emptyNotice.classList.add('d-none');
        if (window.innerWidth < 992 && document.getElementById('orderDocketCol').classList.contains('d-none')) {
            floatingBar.classList.remove('d-none');
        }

        let subtotal = 0;

        cart.forEach((item, idx) => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;

            const div = document.createElement('div');
            div.className = 'p-2 border rounded-3 bg-white shadow-sm';
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge ${item.department === 'bar' ? 'badge-dept-bar' : 'badge-dept-kitchen'} text-uppercase rounded-pill me-1">
                            ${item.department}
                        </span>
                        <strong class="text-dark small">${item.name}</strong>
                    </div>
                    <span class="font-mono fw-bold small text-dark">${itemTotal.toFixed(2)} ETB</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 gap-2">
                    <input type="text" class="form-control form-control-sm py-0 px-2 text-muted rounded-pill" style="font-size: 0.72rem;"
                           placeholder="Item note (e.g. extra spicy, no sugar)..." value="${item.special_instructions || ''}"
                           onchange="updateItemNote(${idx}, this.value)">
                    <div class="btn-group btn-group-sm rounded-pill border">
                        <button type="button" class="btn btn-light py-0 px-2 text-danger fw-bold" onclick="updateQty(${idx}, -1)">-</button>
                        <span class="btn btn-light py-0 px-2 fw-bold font-mono" style="pointer-events: none;">${item.quantity}</span>
                        <button type="button" class="btn btn-light py-0 px-2 text-success fw-bold" onclick="updateQty(${idx}, 1)">+</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });

        const serviceCharge = subtotal * 0.10;
        const tax = (subtotal + serviceCharge) * 0.15;
        const total = subtotal + serviceCharge + tax;

        const formattedTotal = total.toFixed(2) + ' ETB';
        document.getElementById('docketSubtotal').textContent = subtotal.toFixed(2) + ' ETB';
        document.getElementById('docketTaxes').textContent = (serviceCharge + tax).toFixed(2) + ' ETB';
        document.getElementById('docketTotal').textContent = formattedTotal;
        floatingTotal.textContent = formattedTotal;
    }

    function submitPosOrder() {
        if (cart.length === 0) {
            alert('Please add items to the order ticket before dispatching.');
            return;
        }

        const orderType = document.querySelector('input[name="order_type"]:checked').value;
        const tableId = document.getElementById('restaurant_table_id').value;

        if (orderType === 'dine_in' && !tableId) {
            alert('Please select a dining table for Dine-in orders.');
            document.getElementById('restaurant_table_id').focus();
            return;
        }

        const payload = {
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            order_type: orderType,
            restaurant_table_id: orderType === 'dine_in' ? tableId : null,
            customer_name: document.getElementById('customer_name')?.value || null,
            customer_phone: document.getElementById('customer_phone')?.value || null,
            delivery_address: document.getElementById('delivery_address')?.value || null,
            notes: document.getElementById('order_notes')?.value || null,
            items: cart.map(i => ({
                menu_item_id: i.menu_item_id,
                quantity: i.quantity,
                special_instructions: i.special_instructions,
                modifiers: []
            }))
        };

        const btn = document.getElementById('btnSendToKitchen');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Dispatching Order...';

        fetch("{{ route('pos.orders.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i> Send to Kitchen & Bar';

            if (data.success && data.order) {
                lastCreatedOrder = data.order;
                showOrderSuccessModal(data.order);
                cart = [];
                renderCart();
                document.getElementById('order_notes').value = '';
            } else {
                alert(data.message || 'Error processing order');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i> Send to Kitchen & Bar';
            alert('Network error while dispatching order. Please try again.');
        });
    }

    function showOrderSuccessModal(order) {
        document.getElementById('modalOrderNumber').textContent = order.order_number;
        const tableName = order.table ? ('Table ' + order.table.table_number + (order.table.dining_area ? ' (' + order.table.dining_area.name + ')' : '')) : order.order_type.toUpperCase();
        document.getElementById('modalOrderMeta').textContent = `${order.order_type.replace('_', ' ').toUpperCase()} • ${tableName} • Server: ${order.waiter ? order.waiter.name : 'Staff'}`;

        const printBtnsContainer = document.getElementById('modalPrintButtonsContainer');
        printBtnsContainer.innerHTML = '';

        if (order.tickets && order.tickets.length > 0) {
            order.tickets.forEach(ticket => {
                const isKitchen = ticket.department === 'kitchen';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `btn btn-sm ${isKitchen ? 'btn-outline-danger' : 'btn-outline-primary'} d-flex align-items-center justify-content-between p-2 rounded-3`;
                btn.innerHTML = `
                    <span>
                        <i class="bi ${isKitchen ? 'bi-fire' : 'bi-cup-straw'} me-1"></i>
                        Print <strong>${isKitchen ? 'Kitchen KOT' : 'Bar BOT'}</strong> (${ticket.ticket_number})
                    </span>
                    <span class="badge ${isKitchen ? 'bg-danger' : 'bg-primary'} text-white font-mono"><i class="bi bi-printer me-1"></i>IRP 200D</span>
                `;
                btn.onclick = () => printDepartmentTicket(ticket, order);
                printBtnsContainer.appendChild(btn);
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('orderSuccessModal'));
        modal.show();
    }

    function resetPos() {
        cart = [];
        renderCart();
        document.getElementById('order_notes').value = '';
        if (window.innerWidth < 992) {
            switchMobileView('menu');
        }
    }

    // =========================================================================
    // IRP 200D THERMAL PRINTER TICKET FORMATTER (80mm Thermal Receipt Engine)
    // =========================================================================
    function printDepartmentTicket(ticket, order) {
        const isKitchen = ticket.department === 'kitchen';
        const titleBanner = isKitchen ? '*** KITCHEN ORDER TICKET (KOT) ***' : '*** BAR ORDER TICKET (BOT) ***';
        const dateStr = new Date().toLocaleString();
        const tableName = order.table ? `TABLE ${order.table.table_number}` : order.order_type.toUpperCase();
        const areaName = order.table && order.table.dining_area ? order.table.dining_area.name : '';

        let itemsRows = '';
        let totalItems = 0;

        ticket.items.forEach(tItem => {
            const qty = parseFloat(tItem.quantity);
            totalItems += qty;
            const itemName = tItem.order_item?.menu_item?.name || 'Item';
            const notes = tItem.special_instructions ? `<div class="irp200d-item-notes">>> Note: ${tItem.special_instructions}</div>` : '';

            itemsRows += `
                <tr>
                    <td class="irp200d-item-qty">[ ${qty} ]</td>
                    <td class="irp200d-item-name">
                        ${itemName}
                        ${notes}
                    </td>
                </tr>
            `;
        });

        const html = `
            <div class="irp200d-ticket-header">
                <div class="irp200d-title">KEREFA RESTAURANT</div>
                <div style="font-size: 10px; margin-top: 1px;">Thermal Order Printer - IRP 200D</div>
                <div class="irp200d-banner">${titleBanner}</div>
            </div>

            <div class="irp200d-meta-row">
                <span><strong>TICKET :</strong> ${ticket.ticket_number}</span>
                <span><strong>ORDER :</strong> ${order.order_number}</span>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>TARGET :</strong> ${tableName} ${areaName ? '('+areaName+')' : ''}</span>
            </div>
            <div class="irp200d-meta-row">
                <span><strong>WAITER :</strong> ${order.waiter ? order.waiter.name : 'Staff'}</span>
                <span>${dateStr}</span>
            </div>
            ${order.notes ? `<div style="font-size: 11px; margin-top: 2px;"><strong>ORDER NOTE:</strong> ${order.notes}</div>` : ''}

            <table class="irp200d-items-table">
                <thead>
                    <tr>
                        <th style="width: 32px;">QTY</th>
                        <th>ITEM / SPECIAL PREPARATION</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsRows}
                </tbody>
            </table>

            <div class="irp200d-meta-row" style="font-size: 12px; font-weight: bold;">
                <span>TOTAL DISPATCHED:</span>
                <span>${totalItems} ITEMS</span>
            </div>

            <div class="irp200d-cutter-feed">
                - - - - - - - - - - - - - [ IRP 200D CUT LINE ] - - - - - - - - - - - - -
            </div>
        `;

        window.printIrp200dTicket(html);
    }
</script>
@endpush
@endsection
