import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { buildIrp200dTicketHtml, printIrp200d } from './thermalPrinter';

export default function PosApp({ initialData }) {
    // Initial State Unpack
    const branch = initialData.branch || { id: 1, name: 'Main Branch' };
    const user = initialData.user || { id: 1, name: 'Cashier' };
    const roleName = initialData.role || 'Staff';
    const categories = initialData.categories || [];
    const diningAreas = initialData.diningAreas || [];
    const initialOrders = initialData.recentOrders || [];
    const routes = initialData.routes || {};
    const csrfToken = initialData.csrfToken || '';
    const appName = initialData.appName || 'Kerefa Restaurant';

    // State
    const [activeTab, setActiveTab] = useState('menu'); // 'menu' | 'table_numpad' | 'cart' | 'orders'
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [orderType, setOrderType] = useState('dine_in'); // 'dine_in' | 'takeaway' | 'delivery'
    const [selectedTable, setSelectedTable] = useState(null); // { id, table_number, area_name }
    const [cart, setCart] = useState([]);
    const [recentOrders, setRecentOrders] = useState(initialOrders);
    const [ordersFilter, setOrdersFilter] = useState('all'); // 'all' | 'mine'
    const [numpadTableInput, setNumpadTableInput] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [successOrder, setSuccessOrder] = useState(null);
    const [currentTime, setCurrentTime] = useState(new Date());

    // Live Clock
    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    // Flatten all tables across dining areas
    const allTablesList = useMemo(() => {
        const list = [];
        diningAreas.forEach(area => {
            if (area.tables && Array.isArray(area.tables)) {
                area.tables.forEach(tbl => {
                    list.push({
                        ...tbl,
                        area_name: area.name
                    });
                });
            }
        });
        return list;
    }, [diningAreas]);

    // Active table matched by Numpad input
    const numpadMatchedTable = useMemo(() => {
        if (!numpadTableInput) return null;
        return allTablesList.find(t => String(t.table_number) === String(numpadTableInput)) || null;
    }, [numpadTableInput, allTablesList]);

    // Flattened menu items
    const allMenuItems = useMemo(() => {
        const items = [];
        categories.forEach(cat => {
            if (cat.menu_items && Array.isArray(cat.menu_items)) {
                cat.menu_items.forEach(item => {
                    items.push({
                        ...item,
                        category_id: cat.id,
                        category_name: cat.name
                    });
                });
            }
        });
        return items;
    }, [categories]);

    // Filtered menu items
    const displayedItems = useMemo(() => {
        let items = allMenuItems;
        if (selectedCategory !== 'all') {
            items = items.filter(it => it.category_id === selectedCategory);
        }
        if (searchQuery.trim()) {
            const q = searchQuery.toLowerCase().trim();
            items = items.filter(it => 
                it.name.toLowerCase().includes(q) || 
                (it.code && it.code.toLowerCase().includes(q))
            );
        }
        return items;
    }, [allMenuItems, selectedCategory, searchQuery]);

    // Cart calculations
    const cartTotals = useMemo(() => {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        return {
            subtotal,
            grandTotal: subtotal,
            itemCount
        };
    }, [cart]);

    // Cart Actions
    const addToCart = useCallback((item) => {
        setCart(prev => {
            const existingIndex = prev.findIndex(ci => ci.id === item.id);
            if (existingIndex > -1) {
                const next = [...prev];
                next[existingIndex] = {
                    ...next[existingIndex],
                    quantity: next[existingIndex].quantity + 1
                };
                return next;
            }

            const stationType = item.order_station ? item.order_station.station_type : (item.preparation_department || 'kitchen');
            const stationName = item.order_station ? item.order_station.name : (stationType === 'bar' ? 'Central Bar' : 'Main Kitchen');

            return [...prev, {
                id: item.id,
                name: item.name,
                code: item.code,
                price: parseFloat(item.price),
                quantity: 1,
                stationType,
                stationName,
                notes: ''
            }];
        });
    }, []);

    const updateQuantity = useCallback((itemId, delta) => {
        setCart(prev => {
            return prev.map(item => {
                if (item.id === itemId) {
                    const newQty = item.quantity + delta;
                    return newQty > 0 ? { ...item, quantity: newQty } : null;
                }
                return item;
            }).filter(Boolean);
        });
    }, []);

    const removeFromCart = useCallback((itemId) => {
        setCart(prev => prev.filter(it => it.id !== itemId));
    }, []);

    const clearCart = useCallback(() => {
        if (cart.length === 0) return;
        if (window.confirm('Clear all items from current order?')) {
            setCart([]);
        }
    }, [cart]);

    // Touch Numpad Table Selector Actions
    const handleNumpadDigit = useCallback((digit) => {
        setNumpadTableInput(prev => {
            if (prev.length >= 4) return prev;
            return prev + digit;
        });
    }, []);

    const handleNumpadClear = useCallback(() => {
        setNumpadTableInput('');
    }, []);

    const handleNumpadBackspace = useCallback(() => {
        setNumpadTableInput(prev => prev.slice(0, -1));
    }, []);

    const applyNumpadTable = useCallback((tableObj) => {
        const target = tableObj || numpadMatchedTable;
        if (!target) {
            if (!numpadTableInput) {
                alert('Please enter or tap a table number.');
            } else {
                alert(`Table T-${numpadTableInput} was not found on this branch.`);
            }
            return;
        }

        setSelectedTable({
            id: target.id,
            table_number: target.table_number,
            area_name: target.area_name || ''
        });
        setOrderType('dine_in');
        setNumpadTableInput('');
        setActiveTab('menu');
    }, [numpadMatchedTable, numpadTableInput]);

    // Submit Order & Print to IRP 200D
    const handleSubmitOrder = async () => {
        if (cart.length === 0) {
            alert('Cannot place an empty order. Please add menu items.');
            return;
        }

        if (orderType === 'dine_in' && !selectedTable) {
            alert('Please select a dining table for Dine-In orders.');
            setActiveTab('table_numpad');
            return;
        }

        setIsSubmitting(true);

        const payload = {
            order_type: orderType,
            restaurant_table_id: selectedTable ? selectedTable.id : null,
            customer_name: null,
            customer_phone: null,
            delivery_address: null,
            notes: null,
            items: cart.map(item => ({
                menu_item_id: item.id,
                quantity: item.quantity,
                unit_price: item.price,
                notes: item.notes || null,
                modifiers: []
            }))
        };

        try {
            const response = await fetch(routes.storeOrder || '/pos/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                alert(data.message || 'Error creating order.');
                setIsSubmitting(false);
                return;
            }

            const newOrder = data.order;

            // Trigger ESC/POS 80mm Print on IRP 200D for each station ticket
            if (newOrder.tickets && newOrder.tickets.length > 0) {
                newOrder.tickets.forEach(ticket => {
                    const ticketHtml = buildIrp200dTicketHtml(newOrder, ticket, appName);
                    printIrp200d(ticketHtml);
                });
            }

            // Update recent orders list
            setRecentOrders(prev => [newOrder, ...prev]);

            // Show confirmation modal
            setSuccessOrder(newOrder);

            // Reset current cart
            setCart([]);
            if (orderType === 'dine_in') {
                allTablesList.forEach(t => {
                    if (t.id === selectedTable.id) {
                        t.status = 'occupied';
                        t.active_order = newOrder;
                    }
                });
                setSelectedTable(null);
            }

            setActiveTab('menu');

        } catch (err) {
            console.error('Order submission failed:', err);
            alert('Server connection error. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    // Keyboard Shortcuts (F6 = Send Order, F7 = Void)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'F6') {
                e.preventDefault();
                handleSubmitOrder();
            } else if (e.key === 'F7') {
                e.preventDefault();
                clearCart();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [handleSubmitOrder, clearCart]);

    // Filtered orders list
    const filteredOrders = useMemo(() => {
        if (ordersFilter === 'mine') {
            return recentOrders.filter(o => o.waiter_id === user.id);
        }
        return recentOrders;
    }, [recentOrders, ordersFilter, user.id]);

    return (
        <div className="d-flex flex-column vh-100 w-100 overflow-hidden bg-light">
            {/* 1. BOOTSTRAP 5 TOP NAVBAR */}
            <nav className="navbar navbar-expand-lg bg-white border-bottom shadow-xs px-3 py-2 shrink-0 z-3">
                <div className="container-fluid p-0 d-flex align-items-center justify-content-between">
                    {/* Brand & Location */}
                    <div className="d-flex align-items-center gap-2">
                        <div className="bg-warning bg-gradient text-white rounded-3 p-2 d-flex align-items-center justify-content-center shadow-xs" style={{ width: '38px', height: '38px' }}>
                            <i className="bi bi-cup-hot-fill fs-5"></i>
                        </div>
                        <div>
                            <div className="fw-bold fs-6 text-dark lh-1 mb-1">{appName}</div>
                            <div className="d-flex align-items-center gap-1 small text-muted">
                                <span className="badge bg-success rounded-circle p-1" style={{ width: '7px', height: '7px' }}></span>
                                <span>{branch.name}</span>
                            </div>
                        </div>
                    </div>

                    {/* Table Quick Pill / Trigger */}
                    <button
                        type="button"
                        onClick={() => setActiveTab('table_numpad')}
                        className={`btn btn-sm rounded-pill fw-bold px-3 py-1.5 d-flex align-items-center gap-2 shadow-xs ${
                            selectedTable 
                                ? 'btn-primary text-white' 
                                : 'btn-outline-warning text-dark border-warning'
                        }`}
                    >
                        <i className="bi bi-hash fs-6"></i>
                        <span>{selectedTable ? `Table: T-${selectedTable.table_number}` : 'Select Table'}</span>
                        <i className="bi bi-chevron-right small opacity-75"></i>
                    </button>

                    {/* System Badges (Desktop / Tablet) */}
                    <div className="d-none d-lg-flex align-items-center gap-2">
                        {/* Staff */}
                        <div className="badge bg-light text-dark border rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-1.5">
                            <i className="bi bi-person-fill text-primary"></i>
                            <span>{user.name}</span>
                            <span className="badge bg-secondary-subtle text-secondary rounded-pill text-uppercase ms-1" style={{ fontSize: '10px' }}>
                                {roleName}
                            </span>
                        </div>

                        {/* Printer Status */}
                        <div className="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 font-mono d-flex align-items-center gap-1.5">
                            <i className="bi bi-printer-fill"></i>
                            <span>IRP 200D Ready (80mm)</span>
                        </div>

                        {/* Live Digital Clock */}
                        <div className="badge bg-light text-dark border rounded-pill px-3 py-2 font-mono d-flex align-items-center gap-1.5">
                            <i className="bi bi-clock-fill text-muted"></i>
                            <span className="fw-bold">
                                {currentTime.toLocaleTimeString('en-US', { hour12: false })}
                            </span>
                        </div>
                    </div>

                    {/* Nav Pills Switcher */}
                    <div className="d-flex align-items-center gap-1 bg-light p-1 rounded-pill border">
                        <button
                            type="button"
                            onClick={() => setActiveTab('menu')}
                            className={`btn btn-sm rounded-pill fw-bold px-3 py-1 ${activeTab === 'menu' ? 'btn-white bg-white text-primary shadow-xs' : 'text-secondary'}`}
                        >
                            <i className="bi bi-grid-fill me-1"></i>
                            <span className="d-none d-sm-inline">Menu</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => setActiveTab('table_numpad')}
                            className={`btn btn-sm rounded-pill fw-bold px-3 py-1 ${activeTab === 'table_numpad' ? 'btn-white bg-white text-primary shadow-xs' : 'text-secondary'}`}
                        >
                            <i className="bi bi-123 me-1"></i>
                            <span className="d-none d-sm-inline">Table Pad</span>
                        </button>

                        <button
                            type="button"
                            onClick={() => setActiveTab('orders')}
                            className={`btn btn-sm rounded-pill fw-bold px-3 py-1 ${activeTab === 'orders' ? 'btn-white bg-white text-primary shadow-xs' : 'text-secondary'}`}
                        >
                            <i className="bi bi-receipt me-1"></i>
                            <span className="d-none d-sm-inline">Orders</span>
                            <span className="badge bg-secondary-subtle text-secondary rounded-pill ms-1 font-mono">{recentOrders.length}</span>
                        </button>

                        <a
                            href={routes.dashboard || '/dashboard'}
                            className="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 fw-bold"
                            title="Exit POS to ERP Dashboard"
                        >
                            <i className="bi bi-box-arrow-right"></i>
                            <span className="d-none d-sm-inline ms-1">Exit</span>
                        </a>
                    </div>
                </div>
            </nav>

            {/* 2. MAIN BOOTSTRAP GRID WORKSPACE */}
            <div className="container-fluid flex-grow-1 p-0 overflow-hidden position-relative">
                <div className="row g-0 h-100">
                    {/* 2A. LEFT COLUMN: ORDER DOCKET CARD (Bootstrap 5 Card) */}
                    <div className={`
                        col-lg-4 col-xl-4 h-100 bg-white border-end shadow-sm z-2 flex-column
                        ${activeTab === 'cart' ? 'd-flex position-absolute top-0 start-0 w-100 h-100 z-3' : 'd-none d-lg-flex'}
                    `}>
                        {/* Docket Header */}
                        <div className="p-3 border-bottom bg-light bg-opacity-50">
                            <div className="d-flex align-items-center justify-content-between mb-2">
                                <div className="d-flex align-items-center gap-2">
                                    {activeTab === 'cart' && (
                                        <button
                                            type="button"
                                            onClick={() => setActiveTab('menu')}
                                            className="btn btn-sm btn-light border d-lg-none"
                                        >
                                            <i className="bi bi-chevron-left"></i>
                                        </button>
                                    )}
                                    <h6 className="fw-bold mb-0 text-dark">Current Order</h6>
                                </div>

                                <div className="badge bg-light text-muted border font-mono">
                                    {cartTotals.itemCount} items
                                </div>
                            </div>

                            {/* Order Type Switcher (Bootstrap Segmented Group) */}
                            <div className="btn-group w-100" role="group">
                                <button
                                    type="button"
                                    onClick={() => setOrderType('dine_in')}
                                    className={`btn btn-sm ${orderType === 'dine_in' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                >
                                    <i className="bi bi-shop me-1"></i> Dine-In
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setOrderType('takeaway');
                                        setSelectedTable(null);
                                    }}
                                    className={`btn btn-sm ${orderType === 'takeaway' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                >
                                    <i className="bi bi-bag-fill me-1"></i> Takeaway
                                </button>
                                <button
                                    type="button"
                                    onClick={() => {
                                        setOrderType('delivery');
                                        setSelectedTable(null);
                                    }}
                                    className={`btn btn-sm ${orderType === 'delivery' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                >
                                    <i className="bi bi-bicycle me-1"></i> Delivery
                                </button>
                            </div>
                        </div>

                        {/* Table Selector Strip */}
                        <div className="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between">
                            <button
                                type="button"
                                onClick={() => setActiveTab('table_numpad')}
                                className="btn btn-link p-0 text-decoration-none fw-bold small text-primary d-flex align-items-center gap-1.5"
                            >
                                <i className="bi bi-hash text-warning"></i>
                                <span>{selectedTable ? `Table: T-${selectedTable.table_number}` : 'Tap to Assign Table'}</span>
                            </button>
                            {selectedTable && (
                                <span className="badge bg-success-subtle text-success border border-success-subtle">
                                    Active Table
                                </span>
                            )}
                        </div>

                        {/* Cart Items List */}
                        <div className="flex-grow-1 overflow-auto p-2">
                            {cart.length === 0 ? (
                                <div className="h-100 d-flex flex-col flex-column align-items-center justify-content-center text-center p-4 text-muted">
                                    <i className="bi bi-cart3 fs-1 opacity-25 mb-2"></i>
                                    <div className="fw-bold text-secondary">Order docket is empty</div>
                                    <div className="small text-muted mb-3">Add food or drinks from the menu</div>
                                    <button
                                        type="button"
                                        onClick={() => setActiveTab('menu')}
                                        className="btn btn-sm btn-primary rounded-pill d-lg-none"
                                    >
                                        Open Menu
                                    </button>
                                </div>
                            ) : (
                                <div className="list-group list-group-flush gap-2">
                                    {cart.map(item => (
                                        <div
                                            key={item.id}
                                            className="list-group-item border rounded-3 p-2.5 bg-white shadow-xs"
                                        >
                                            <div className="d-flex align-items-start justify-content-between mb-1.5">
                                                <div>
                                                    <div className="fw-bold text-dark lh-sm">{item.name}</div>
                                                    <span className={`badge mt-1 ${
                                                        item.stationType === 'bar' 
                                                            ? 'bg-info-subtle text-info-emphasis border border-info-subtle' 
                                                            : 'bg-danger-subtle text-danger border border-danger-subtle'
                                                    }`} style={{ fontSize: '10px' }}>
                                                        {item.stationName}
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeFromCart(item.id)}
                                                    className="btn btn-sm btn-link text-danger p-0 ms-2"
                                                    title="Remove"
                                                >
                                                    <i className="bi bi-x-circle-fill"></i>
                                                </button>
                                            </div>

                                            <div className="d-flex align-items-center justify-content-between pt-1 border-top">
                                                <div className="small text-muted font-mono">
                                                    ETB {item.price.toFixed(2)}
                                                </div>

                                                <div className="d-flex align-items-center gap-2">
                                                    {/* Steppers */}
                                                    <div className="btn-group btn-group-sm">
                                                        <button
                                                            type="button"
                                                            onClick={() => updateQuantity(item.id, -1)}
                                                            className="btn btn-light border py-0 px-2 fw-bold"
                                                        >
                                                            -
                                                        </button>
                                                        <span className="btn btn-light border py-0 px-2.5 fw-bold font-mono">
                                                            {item.quantity}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => updateQuantity(item.id, 1)}
                                                            className="btn btn-light border py-0 px-2 fw-bold"
                                                        >
                                                            +
                                                        </button>
                                                    </div>

                                                    {/* Line Total */}
                                                    <div className="font-mono fw-bold text-dark" style={{ minWidth: '60px', textAlign: 'right' }}>
                                                        {(item.price * item.quantity).toFixed(2)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Cart Footer */}
                        <div className="p-3 bg-white border-top shadow-xs">
                            <div className="d-flex align-items-baseline justify-content-between mb-3">
                                <span className="text-muted fw-bold small text-uppercase">Total Amount</span>
                                <div className="fs-3 fw-bold font-mono text-dark">
                                    <span className="fs-6 text-muted me-1">ETB</span>
                                    {cartTotals.grandTotal.toFixed(2)}
                                </div>
                            </div>

                            <div className="d-grid gap-2" style={{ gridTemplateColumns: '90px 1fr' }}>
                                <button
                                    type="button"
                                    onClick={clearCart}
                                    disabled={cart.length === 0}
                                    className="btn btn-outline-danger fw-bold d-flex flex-column align-items-center justify-content-center py-2"
                                    title="Void Order (F7)"
                                >
                                    <i className="bi bi-trash3-fill mb-0.5"></i>
                                    <span style={{ fontSize: '11px' }}>Void (F7)</span>
                                </button>

                                <button
                                    type="button"
                                    onClick={handleSubmitOrder}
                                    disabled={cart.length === 0 || isSubmitting}
                                    className="btn btn-success btn-lg fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm text-uppercase"
                                    title="Send Order to Kitchen & Bar IRP 200D Printers (F6)"
                                >
                                    {isSubmitting ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm"></span>
                                            <span>Printing...</span>
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-printer-fill fs-5"></i>
                                            <span>Send Order (F6)</span>
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* 2B. RIGHT COLUMN: WORKSPACE (Bootstrap Grid) */}
                    <div className="col-lg-8 col-xl-8 h-100 d-flex flex-column bg-light overflow-hidden">
                        {/* VIEW 1: MENU VIEW */}
                        {activeTab === 'menu' && (
                            <div className="h-100 d-flex flex-column overflow-hidden">
                                {/* Search & Category Pills */}
                                <div className="p-3 bg-white border-bottom shadow-2xs">
                                    <div className="row g-2 align-items-center">
                                        <div className="col-12 col-md-5">
                                            <div className="input-group input-group-sm">
                                                <span className="input-group-text bg-white border-end-0">
                                                    <i className="bi bi-search text-muted"></i>
                                                </span>
                                                <input
                                                    type="text"
                                                    value={searchQuery}
                                                    onChange={(e) => setSearchQuery(e.target.value)}
                                                    placeholder="Search dishes or code..."
                                                    className="form-control border-start-0"
                                                />
                                                {searchQuery && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setSearchQuery('')}
                                                        className="btn btn-outline-secondary"
                                                    >
                                                        <i className="bi bi-x-lg"></i>
                                                    </button>
                                                )}
                                            </div>
                                        </div>

                                        <div className="col-12 col-md-7">
                                            <div className="d-flex align-items-center gap-1.5 overflow-auto pb-1">
                                                <button
                                                    type="button"
                                                    onClick={() => setSelectedCategory('all')}
                                                    className={`btn btn-sm rounded-pill fw-bold text-nowrap ${
                                                        selectedCategory === 'all' 
                                                            ? 'btn-dark' 
                                                            : 'btn-outline-secondary bg-white'
                                                    }`}
                                                >
                                                    All ({allMenuItems.length})
                                                </button>
                                                {categories.map(cat => (
                                                    <button
                                                        key={cat.id}
                                                        type="button"
                                                        onClick={() => setSelectedCategory(cat.id)}
                                                        className={`btn btn-sm rounded-pill fw-bold text-nowrap ${
                                                            selectedCategory === cat.id 
                                                                ? 'btn-primary' 
                                                                : 'btn-outline-secondary bg-white'
                                                        }`}
                                                    >
                                                        {cat.name} ({cat.menu_items ? cat.menu_items.length : 0})
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Menu Items Grid */}
                                <div className="flex-grow-1 overflow-auto p-3 pb-5">
                                    <div className="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-xl-4 g-2 g-sm-3">
                                        {displayedItems.map(item => {
                                            const stationType = item.order_station ? item.order_station.station_type : (item.preparation_department || 'kitchen');
                                            const stationName = item.order_station ? item.order_station.name : (stationType === 'bar' ? 'Bar' : 'Kitchen');

                                            return (
                                                <div key={item.id} className="col">
                                                    <div
                                                        onClick={() => addToCart(item)}
                                                        className="card h-100 border-0 shadow-sm rounded-4 p-3 hover-lift cursor-pointer d-flex flex-column justify-content-between"
                                                    >
                                                        <div>
                                                            <div className="fw-bold text-dark lh-sm mb-1">{item.name}</div>
                                                            <span className={`badge ${
                                                                stationType === 'bar' 
                                                                    ? 'bg-info-subtle text-info-emphasis border border-info-subtle' 
                                                                    : 'bg-danger-subtle text-danger border border-danger-subtle'
                                                            }`} style={{ fontSize: '10px' }}>
                                                                {stationName}
                                                            </span>
                                                        </div>

                                                        <div className="d-flex align-items-baseline justify-content-between pt-2 mt-2 border-top">
                                                            <div className="font-mono fw-bold text-success fs-6">
                                                                <span className="small text-muted me-0.5">ETB</span>
                                                                {parseFloat(item.price).toFixed(2)}
                                                            </div>
                                                            <button
                                                                type="button"
                                                                className="btn btn-sm btn-primary rounded-circle p-1 d-flex align-items-center justify-content-center"
                                                                style={{ width: '28px', height: '28px' }}
                                                            >
                                                                <i className="bi bi-plus-lg"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Floating Cart Bar on Mobile / Small Tablet */}
                                {cart.length > 0 && (
                                    <div className="position-fixed bottom-0 start-0 end-0 p-3 z-3 d-lg-none">
                                        <div
                                            onClick={() => setActiveTab('cart')}
                                            className="card shadow-lg bg-dark text-white border-0 rounded-4 p-3 d-flex flex-row align-items-center justify-content-between cursor-pointer"
                                        >
                                            <div className="d-flex align-items-center gap-2">
                                                <div className="badge bg-primary rounded-circle p-2 fs-6 font-mono">
                                                    {cartTotals.itemCount}
                                                </div>
                                                <div>
                                                    <div className="fw-bold">View Current Order</div>
                                                    <div className="small text-white-50">
                                                        {selectedTable ? `Table T-${selectedTable.table_number}` : 'No table assigned'}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="d-flex align-items-center gap-2">
                                                <div className="font-mono fw-bold fs-5 text-warning">
                                                    ETB {cartTotals.grandTotal.toFixed(2)}
                                                </div>
                                                <i className="bi bi-chevron-right fs-5"></i>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* VIEW 2: NUMERIC PAD TABLE SELECTOR ("use numberk padd table") */}
                        {activeTab === 'table_numpad' && (
                            <div className="h-100 overflow-auto p-3 p-sm-4 d-flex flex-column align-items-center justify-content-center">
                                <div className="card shadow-sm border-0 rounded-4 p-4 mx-auto w-100" style={{ maxWidth: '420px' }}>
                                    <div className="text-center mb-3">
                                        <div className="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis rounded-circle p-3 mb-2" style={{ width: '50px', height: '50px' }}>
                                            <i className="bi bi-123 fs-4"></i>
                                        </div>
                                        <h5 className="fw-bold mb-0 text-dark">Touch Numeric Pad Table</h5>
                                        <div className="small text-muted">Type table number directly on keypad</div>
                                    </div>

                                    {/* Display Box */}
                                    <div className="bg-light border rounded-4 p-3 text-center mb-3">
                                        <div className="small text-muted fw-bold text-uppercase">Selected Table</div>
                                        <div className="fs-1 fw-black font-mono text-dark my-1">
                                            {numpadTableInput ? `T-${numpadTableInput}` : (selectedTable ? `T-${selectedTable.table_number}` : 'T-___')}
                                        </div>
                                        <div>
                                            {numpadMatchedTable ? (
                                                <span className={`badge ${
                                                    numpadMatchedTable.status === 'available'
                                                        ? 'bg-success-subtle text-success border border-success-subtle'
                                                        : 'bg-danger-subtle text-danger border border-danger-subtle'
                                                }`}>
                                                    {numpadMatchedTable.status === 'available' ? 'Available' : 'Occupied'} • {numpadMatchedTable.area_name}
                                                </span>
                                            ) : (
                                                <span className="small text-muted">Type digits (e.g. 1, 4, 12)</span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Touch 3x4 Numpad */}
                                    <div className="row g-2 mb-3">
                                        {['1', '2', '3', '4', '5', '6', '7', '8', '9'].map(digit => (
                                            <div key={digit} className="col-4">
                                                <button
                                                    type="button"
                                                    onClick={() => handleNumpadDigit(digit)}
                                                    className="btn btn-light border py-3 fs-3 fw-bold font-mono rounded-3 w-100 shadow-2xs hover-lift"
                                                >
                                                    {digit}
                                                </button>
                                            </div>
                                        ))}
                                        <div className="col-4">
                                            <button
                                                type="button"
                                                onClick={handleNumpadClear}
                                                className="btn btn-outline-danger py-3 fs-5 fw-bold rounded-3 w-100"
                                            >
                                                AC
                                            </button>
                                        </div>
                                        <div className="col-4">
                                            <button
                                                type="button"
                                                onClick={() => handleNumpadDigit('0')}
                                                className="btn btn-light border py-3 fs-3 fw-bold font-mono rounded-3 w-100 shadow-2xs hover-lift"
                                            >
                                                0
                                            </button>
                                        </div>
                                        <div className="col-4">
                                            <button
                                                type="button"
                                                onClick={handleNumpadBackspace}
                                                className="btn btn-outline-warning text-dark py-3 fs-5 fw-bold rounded-3 w-100"
                                            >
                                                <i className="bi bi-backspace"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {/* Confirm Button */}
                                    <button
                                        type="button"
                                        onClick={() => applyNumpadTable()}
                                        className="btn btn-primary btn-lg w-100 rounded-3 py-3 fw-bold shadow-sm"
                                    >
                                        Apply Table & Go To Menu <i className="bi bi-arrow-right ms-1"></i>
                                    </button>

                                    {/* Quick Available Tables List */}
                                    <div className="mt-4 pt-3 border-top">
                                        <div className="small fw-bold text-muted text-uppercase mb-2">Or Tap a Free Table:</div>
                                        <div className="d-flex flex-wrap gap-1.5" style={{ maxHeight: '120px', overflowY: 'auto' }}>
                                            {allTablesList.filter(t => t.status === 'available').map(tbl => (
                                                <button
                                                    key={tbl.id}
                                                    type="button"
                                                    onClick={() => applyNumpadTable(tbl)}
                                                    className="btn btn-sm btn-outline-success font-mono fw-bold rounded-pill"
                                                >
                                                    T-{tbl.table_number}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* VIEW 3: ORDERS TRACKING */}
                        {activeTab === 'orders' && (
                            <div className="h-100 overflow-auto p-3">
                                <div className="card border-0 shadow-sm rounded-4 overflow-hidden">
                                    <div className="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                        <div className="d-flex gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setOrdersFilter('all')}
                                                className={`btn btn-sm rounded-pill fw-bold ${ordersFilter === 'all' ? 'btn-dark' : 'btn-outline-secondary'}`}
                                            >
                                                All Orders ({recentOrders.length})
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setOrdersFilter('mine')}
                                                className={`btn btn-sm rounded-pill fw-bold ${ordersFilter === 'mine' ? 'btn-dark' : 'btn-outline-secondary'}`}
                                            >
                                                My Orders ({recentOrders.filter(o => o.waiter_id === user.id).length})
                                            </button>
                                        </div>

                                        <div className="small text-muted font-mono">
                                            Thermal Printer: <strong>IRP 200D</strong>
                                        </div>
                                    </div>

                                    <div className="table-responsive">
                                        <table className="table table-hover align-middle mb-0 font-sans small">
                                            <thead className="table-light text-muted uppercase">
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Table</th>
                                                    <th>Total</th>
                                                    <th>Time</th>
                                                    <th>Station Tickets</th>
                                                    <th className="text-end">Reprint</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {filteredOrders.length === 0 ? (
                                                    <tr>
                                                        <td colSpan="6" className="text-center py-5 text-muted">
                                                            No orders placed yet.
                                                        </td>
                                                    </tr>
                                                ) : (
                                                    filteredOrders.map(order => (
                                                        <tr key={order.id}>
                                                            <td className="font-mono fw-bold text-primary">{order.order_number}</td>
                                                            <td className="fw-bold">{order.table ? `T-${order.table.table_number}` : 'Takeaway'}</td>
                                                            <td className="font-mono fw-bold">ETB {parseFloat(order.total_amount).toFixed(2)}</td>
                                                            <td className="text-muted font-mono">{new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                                                            <td>
                                                                <div className="d-flex gap-1">
                                                                    {order.tickets && order.tickets.map(t => (
                                                                        <span key={t.id} className="badge bg-secondary">
                                                                            {t.ticket_type === 'bar' ? 'Bar' : 'Kitchen'} #{t.ticket_number}
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            </td>
                                                            <td className="text-end">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        if (order.tickets && order.tickets.length > 0) {
                                                                            order.tickets.forEach(ticket => {
                                                                                const html = buildIrp200dTicketHtml(order, ticket, appName);
                                                                                printIrp200d(html);
                                                                            });
                                                                        } else {
                                                                            alert('No station tickets for this order.');
                                                                        }
                                                                    }}
                                                                    className="btn btn-sm btn-outline-primary font-mono py-1 px-2"
                                                                >
                                                                    <i className="bi bi-printer-fill me-1"></i> IRP 200D
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* 3. ORDER DISPATCH SUCCESS MODAL (Bootstrap 5 Modal) */}
            {successOrder && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(15, 23, 42, 0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered" style={{ maxWidth: '400px' }}>
                        <div className="modal-content border-0 shadow-lg rounded-4 p-4 text-center">
                            <div className="bg-success-subtle text-success rounded-circle mx-auto p-3 mb-2 d-flex align-items-center justify-content-center" style={{ width: '60px', height: '60px' }}>
                                <i className="bi bi-check-lg fs-2"></i>
                            </div>

                            <h5 className="fw-bold text-dark mb-1">Order Dispatched</h5>
                            <div className="font-mono fw-bold fs-3 text-primary mb-2">
                                {successOrder.order_number}
                            </div>

                            <p className="text-muted small mb-3">
                                {successOrder.table ? `Table: T-${successOrder.table.table_number}` : 'Takeaway'} • Total: ETB {parseFloat(successOrder.total_amount).toFixed(2)}
                            </p>

                            <div className="p-3 bg-light rounded-3 text-start small font-mono mb-4 border">
                                <div className="fw-bold text-dark mb-1 d-flex align-items-center gap-1.5">
                                    <i className="bi bi-printer-fill text-success"></i>
                                    <span>IRP 200D Tickets Printed:</span>
                                </div>
                                {successOrder.tickets && successOrder.tickets.map(ticket => (
                                    <div key={ticket.id} className="text-success fw-bold">
                                        ✔ {ticket.ticket_type === 'bar' ? 'Central Bar' : 'Main Kitchen'} Ticket #{ticket.ticket_number}
                                    </div>
                                ))}
                            </div>

                            <button
                                type="button"
                                onClick={() => setSuccessOrder(null)}
                                className="btn btn-dark btn-lg w-100 rounded-3 py-2.5 fw-bold"
                            >
                                Next Order
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
