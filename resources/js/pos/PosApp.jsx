import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { 
    Utensils, LayoutGrid, Coffee, Beer, Wine, Flame, Clock, 
    Printer, Trash2, Plus, Minus, Search, CheckCircle2, 
    LogOut, AlertCircle, RefreshCw, X, ChevronRight, ShoppingBag, 
    Store, Bike, UserCheck, Star, Hash, ArrowRight, Delete, ChevronLeft
} from 'lucide-react';
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
    const [activeTab, setActiveTab] = useState('menu'); // 'menu' | 'table_numpad' | 'cart' | 'orders' | 'bestsellers'
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

            // Switch to Menu or Orders
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
        <div className="flex flex-col h-screen w-screen overflow-hidden bg-slate-50 text-slate-800 font-sans antialiased">
            {/* 1. TOP RESPONSIVE HEADER */}
            <header className="h-14 sm:h-16 bg-white border-b border-slate-200/80 px-3 sm:px-4 flex items-center justify-between shadow-xs shrink-0 z-30">
                {/* Brand & Table Indicator */}
                <div className="flex items-center gap-2 sm:gap-3">
                    <div className="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-linear-to-tr from-amber-600 to-amber-500 text-white flex items-center justify-center shadow-xs shrink-0">
                        <Utensils className="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <div>
                        <div className="font-extrabold text-sm sm:text-base tracking-tight text-slate-900 leading-tight">
                            {appName}
                        </div>
                        <div className="flex items-center gap-1.5 text-[11px] sm:text-xs text-slate-500 font-medium">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span className="truncate max-w-[120px] sm:max-w-none">{branch.name}</span>
                        </div>
                    </div>
                </div>

                {/* Table Numpad Trigger (Prominent Quick Pill) */}
                <button
                    type="button"
                    onClick={() => setActiveTab('table_numpad')}
                    className={`flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-1.5 rounded-xl border text-xs font-extrabold transition-all shadow-2xs ${
                        selectedTable 
                            ? 'bg-sky-50 border-sky-300 text-sky-700 ring-2 ring-sky-500/20' 
                            : 'bg-amber-50 border-amber-300 text-amber-800 hover:bg-amber-100'
                    }`}
                >
                    <Hash className="w-4 h-4" />
                    <span>{selectedTable ? `T-${selectedTable.table_number}` : 'Select Table'}</span>
                    <ChevronRight className="w-3 h-3 text-slate-400" />
                </button>

                {/* Navigation Tabs Bar */}
                <nav className="flex items-center gap-1 bg-slate-100/90 p-1 rounded-xl border border-slate-200/80">
                    <button 
                        type="button"
                        onClick={() => setActiveTab('menu')}
                        className={`flex items-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'menu' 
                                ? 'bg-white text-sky-700 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <LayoutGrid className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Menu</span>
                    </button>

                    <button 
                        type="button"
                        onClick={() => setActiveTab('table_numpad')}
                        className={`flex items-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'table_numpad' 
                                ? 'bg-white text-sky-700 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <Hash className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Table Pad</span>
                    </button>

                    <button 
                        type="button"
                        onClick={() => setActiveTab('orders')}
                        className={`flex items-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'orders' 
                                ? 'bg-white text-sky-700 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900'
                        }`}
                    >
                        <Clock className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Orders</span>
                    </button>

                    <a 
                        href={routes.dashboard || '/dashboard'}
                        className="flex items-center gap-1 px-2 sm:px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 transition-all"
                        title="Exit POS to ERP Dashboard"
                    >
                        <LogOut className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">Exit</span>
                    </a>
                </nav>
            </header>

            {/* 2. MAIN ADAPTIVE WORKSPACE */}
            <div className="flex flex-1 overflow-hidden relative">
                {/* 2A. LEFT DOCKET (Visible side-by-side on lg: screens; switchable or drawer on mobile/tablet) */}
                <aside className={`
                    lg:flex flex-col h-full bg-white border-r border-slate-200/80 shadow-sm z-20 shrink-0
                    ${activeTab === 'cart' ? 'flex absolute inset-0 z-40 w-full' : 'hidden lg:w-[360px] xl:w-[390px]'}
                `}>
                    {/* Cart Header on Mobile/Tablet */}
                    <div className="p-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            {activeTab === 'cart' && (
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('menu')}
                                    className="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 lg:hidden"
                                >
                                    <ChevronLeft className="w-4 h-4" />
                                </button>
                            )}
                            <div className="font-extrabold text-sm text-slate-800">
                                Current Order
                            </div>
                        </div>

                        {/* Order Type Chips */}
                        <div className="flex items-center gap-1 bg-slate-200/70 p-0.5 rounded-lg text-[11px] font-bold">
                            <button
                                type="button"
                                onClick={() => setOrderType('dine_in')}
                                className={`px-2 py-1 rounded-md transition-all ${
                                    orderType === 'dine_in' ? 'bg-white text-sky-700 shadow-2xs' : 'text-slate-600'
                                }`}
                            >
                                Dine-In
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setOrderType('takeaway');
                                    setSelectedTable(null);
                                }}
                                className={`px-2 py-1 rounded-md transition-all ${
                                    orderType === 'takeaway' ? 'bg-white text-sky-700 shadow-2xs' : 'text-slate-600'
                                }`}
                            >
                                Takeaway
                            </button>
                        </div>
                    </div>

                    {/* Table Assignment Bar in Cart */}
                    <div className="px-3 py-2 bg-slate-100/50 border-b border-slate-200/60 flex items-center justify-between text-xs">
                        <button
                            type="button"
                            onClick={() => setActiveTab('table_numpad')}
                            className="flex items-center gap-1.5 font-bold text-sky-700 hover:underline"
                        >
                            <Hash className="w-3.5 h-3.5 text-amber-500" />
                            <span>{selectedTable ? `Table: T-${selectedTable.table_number}` : 'Tap to Assign Table'}</span>
                        </button>
                        <span className="font-mono text-slate-400 font-bold">
                            {cartTotals.itemCount} items
                        </span>
                    </div>

                    {/* Items List */}
                    <div className="flex-1 overflow-y-auto p-3 space-y-2">
                        {cart.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                                <div className="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 mb-2">
                                    <ShoppingBag className="w-6 h-6" />
                                </div>
                                <div className="font-bold text-slate-600 text-sm">Order is empty</div>
                                <div className="text-xs text-slate-400 mt-0.5">
                                    Tap dishes from the menu to add
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('menu')}
                                    className="mt-3 px-4 py-1.5 rounded-lg bg-sky-600 text-white font-bold text-xs lg:hidden"
                                >
                                    Browse Menu
                                </button>
                            </div>
                        ) : (
                            cart.map(item => (
                                <div 
                                    key={item.id}
                                    className="p-2.5 rounded-xl border border-slate-200/90 bg-white shadow-2xs flex items-center justify-between gap-2"
                                >
                                    <div className="flex-1 min-w-0">
                                        <div className="font-bold text-sm text-slate-800 truncate">
                                            {item.name}
                                        </div>
                                        <div className="flex items-center gap-2 mt-0.5">
                                            <span className={`text-[10px] font-extrabold px-1.5 py-0.2 rounded-md ${
                                                item.stationType === 'bar' 
                                                    ? 'bg-sky-50 text-sky-700 border border-sky-200' 
                                                    : 'bg-rose-50 text-rose-700 border border-rose-200'
                                            }`}>
                                                {item.stationName}
                                            </span>
                                            <span className="font-mono text-xs text-slate-500 font-medium">
                                                ETB {item.price.toFixed(2)}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Qty Steppers */}
                                    <div className="flex items-center gap-1.5 bg-slate-100 p-1 rounded-lg shrink-0">
                                        <button 
                                            type="button"
                                            onClick={() => updateQuantity(item.id, -1)}
                                            className="w-6 h-6 rounded bg-white text-slate-700 hover:bg-slate-200 flex items-center justify-center text-xs font-bold shadow-2xs"
                                        >
                                            <Minus className="w-3 h-3" />
                                        </button>
                                        <span className="font-mono text-xs font-black min-w-[16px] text-center">
                                            {item.quantity}
                                        </span>
                                        <button 
                                            type="button"
                                            onClick={() => updateQuantity(item.id, 1)}
                                            className="w-6 h-6 rounded bg-white text-slate-700 hover:bg-slate-200 flex items-center justify-center text-xs font-bold shadow-2xs"
                                        >
                                            <Plus className="w-3 h-3" />
                                        </button>
                                    </div>

                                    {/* Total */}
                                    <div className="text-right font-mono text-xs font-extrabold text-slate-800 min-w-[50px]">
                                        {(item.price * item.quantity).toFixed(2)}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Cart Footer */}
                    <div className="p-3 sm:p-4 bg-white border-t border-slate-200/80 space-y-2.5 shrink-0 shadow-xs">
                        <div className="flex items-baseline justify-between">
                            <span className="text-xs font-extrabold uppercase text-slate-400">Total</span>
                            <div className="font-mono font-black text-xl sm:text-2xl text-slate-900">
                                <span className="text-xs font-bold text-slate-400 mr-1">ETB</span>
                                {cartTotals.grandTotal.toFixed(2)}
                            </div>
                        </div>

                        <div className="grid grid-cols-[80px_1fr] gap-2">
                            <button
                                type="button"
                                onClick={clearCart}
                                disabled={cart.length === 0}
                                className="flex flex-col items-center justify-center py-2.5 rounded-xl border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 font-extrabold text-xs transition-all disabled:opacity-40"
                                title="Void Order (F7)"
                            >
                                <Trash2 className="w-3.5 h-3.5 mb-0.5" />
                                <span>Void (F7)</span>
                            </button>

                            <button
                                type="button"
                                onClick={handleSubmitOrder}
                                disabled={cart.length === 0 || isSubmitting}
                                className="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm shadow-md shadow-emerald-600/20 transition-all disabled:opacity-40 active:scale-[0.98]"
                                title="Send Order to Kitchen & Bar IRP 200D Printers (F6)"
                            >
                                {isSubmitting ? (
                                    <>
                                        <RefreshCw className="w-4 h-4 animate-spin" />
                                        <span>Printing...</span>
                                    </>
                                ) : (
                                    <>
                                        <Printer className="w-4 h-4" />
                                        <span>SEND ORDER (F6)</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </aside>

                {/* 2B. RIGHT MAIN AREA (Dynamic: Menu, Numeric Table Pad, Orders) */}
                <main className="flex-1 flex flex-col overflow-hidden bg-slate-100/60">
                    {/* VIEW 1: MENU (Responsive 2-col on phones, 3-4 col on tablets, 4-6 col on desktop) */}
                    {activeTab === 'menu' && (
                        <div className="flex-1 flex flex-col h-full overflow-hidden">
                            {/* Search & Category Filter Bar */}
                            <div className="p-3 bg-white border-b border-slate-200/80 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 shrink-0">
                                {/* Horizontal Categories Chips */}
                                <div className="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                                    <button
                                        type="button"
                                        onClick={() => setSelectedCategory('all')}
                                        className={`px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all shrink-0 ${
                                            selectedCategory === 'all'
                                                ? 'bg-slate-900 text-white shadow-xs'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        }`}
                                    >
                                        All Dishes ({allMenuItems.length})
                                    </button>
                                    {categories.map(cat => (
                                        <button
                                            key={cat.id}
                                            type="button"
                                            onClick={() => setSelectedCategory(cat.id)}
                                            className={`px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all shrink-0 ${
                                                selectedCategory === cat.id
                                                    ? 'bg-sky-600 text-white shadow-xs'
                                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                            }`}
                                        >
                                            {cat.name} ({cat.menu_items ? cat.menu_items.length : 0})
                                        </button>
                                    ))}
                                </div>

                                {/* Search Bar */}
                                <div className="relative w-full sm:w-56 shrink-0">
                                    <Search className="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input 
                                        type="text" 
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="Search dishes..." 
                                        className="w-full pl-8 pr-7 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-sky-500 transition-all"
                                    />
                                    {searchQuery && (
                                        <button 
                                            onClick={() => setSearchQuery('')}
                                            className="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        >
                                            <X className="w-3.5 h-3.5" />
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Products Grid (Mobile 2-col, Tablet 3-4 col, Desktop 4-5 col) */}
                            <div className="flex-1 overflow-y-auto p-3 sm:p-4 pb-20 lg:pb-4">
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-2.5 sm:gap-3.5">
                                    {displayedItems.map(item => {
                                        const stationType = item.order_station ? item.order_station.station_type : (item.preparation_department || 'kitchen');
                                        const stationName = item.order_station ? item.order_station.name : (stationType === 'bar' ? 'Bar' : 'Kitchen');

                                        return (
                                            <div 
                                                key={item.id}
                                                onClick={() => addToCart(item)}
                                                className="group p-3 rounded-2xl bg-white border border-slate-200/90 hover:border-sky-300 hover:shadow-md transition-all cursor-pointer flex flex-col justify-between min-h-[115px] sm:min-h-[125px] active:scale-[0.97]"
                                            >
                                                <div>
                                                    <div className="font-extrabold text-xs sm:text-sm text-slate-800 leading-snug group-hover:text-sky-700 transition-colors line-clamp-2">
                                                        {item.name}
                                                    </div>
                                                    <div className="flex items-center gap-1 mt-1">
                                                        <span className={`text-[9px] sm:text-[10px] font-extrabold px-1.5 py-0.2 rounded-md ${
                                                            stationType === 'bar' 
                                                                ? 'bg-sky-50 text-sky-700 border border-sky-200/80' 
                                                                : 'bg-rose-50 text-rose-700 border border-rose-200/80'
                                                        }`}>
                                                            {stationName}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="flex items-baseline justify-between mt-2 pt-2 border-t border-slate-100">
                                                    <div className="font-mono font-black text-xs sm:text-sm text-emerald-700">
                                                        <span className="text-[9px] font-bold text-slate-400 mr-0.5">ETB</span>
                                                        {parseFloat(item.price).toFixed(2)}
                                                    </div>
                                                    <div className="w-5 h-5 sm:w-6 sm:h-6 rounded-lg bg-slate-100 group-hover:bg-sky-600 group-hover:text-white text-slate-500 flex items-center justify-center transition-colors">
                                                        <Plus className="w-3 h-3" />
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* FLOATING MOBILE CART BAR (Visible on mobile/tablet when items in cart) */}
                            {cart.length > 0 && (
                                <div className="lg:hidden fixed bottom-3 left-3 right-3 z-30 animate-in fade-in slide-in-from-bottom-2">
                                    <div 
                                        onClick={() => setActiveTab('cart')}
                                        className="bg-slate-900 text-white rounded-2xl p-3 shadow-xl flex items-center justify-between cursor-pointer border border-slate-700 active:scale-[0.99]"
                                    >
                                        <div className="flex items-center gap-2.5">
                                            <div className="w-9 h-9 rounded-xl bg-sky-600 flex items-center justify-center font-bold text-sm font-mono">
                                                {cartTotals.itemCount}
                                            </div>
                                            <div>
                                                <div className="font-extrabold text-sm leading-tight">View Current Order</div>
                                                <div className="text-[11px] text-slate-400">
                                                    {selectedTable ? `Table T-${selectedTable.table_number}` : 'No table assigned'}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3">
                                            <div className="font-mono font-black text-base text-emerald-400">
                                                ETB {cartTotals.grandTotal.toFixed(2)}
                                            </div>
                                            <div className="bg-white/10 px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1">
                                                <span>Cart</span>
                                                <ChevronRight className="w-3.5 h-3.5" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* VIEW 2: NUMERIC PAD TABLE SELECTOR ("use numberk padd table") */}
                    {activeTab === 'table_numpad' && (
                        <div className="flex-1 overflow-y-auto p-3 sm:p-6 flex flex-col items-center justify-center">
                            <div className="max-w-md w-full bg-white rounded-3xl border border-slate-200/90 p-4 sm:p-6 shadow-sm">
                                {/* Title & Instructions */}
                                <div className="text-center mb-3">
                                    <div className="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 mb-2">
                                        <Hash className="w-5 h-5" />
                                    </div>
                                    <h2 className="font-black text-lg text-slate-800">
                                        Touch Numeric Pad Table
                                    </h2>
                                    <p className="text-xs text-slate-400">
                                        Enter table number directly on the keypad
                                    </p>
                                </div>

                                {/* Table Display Screen */}
                                <div className="bg-slate-50 border-2 border-slate-200/80 rounded-2xl p-4 text-center mb-4 transition-all">
                                    <div className="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">
                                        Selected Table
                                    </div>
                                    <div className="font-mono font-black text-4xl sm:text-5xl text-slate-900 tracking-tight my-1">
                                        {numpadTableInput ? `T-${numpadTableInput}` : (selectedTable ? `T-${selectedTable.table_number}` : 'T-___')}
                                    </div>

                                    {/* Table Status Hint */}
                                    <div className="text-xs font-semibold">
                                        {numpadMatchedTable ? (
                                            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full ${
                                                numpadMatchedTable.status === 'available'
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-rose-100 text-rose-800'
                                            }`}>
                                                <span className={`w-1.5 h-1.5 rounded-full ${numpadMatchedTable.status === 'available' ? 'bg-emerald-600' : 'bg-rose-600'}`}></span>
                                                <span>{numpadMatchedTable.status === 'available' ? 'Available' : 'Occupied'} • {numpadMatchedTable.area_name}</span>
                                            </span>
                                        ) : (
                                            <span className="text-slate-400">Type table number (e.g. 1, 4, 12)</span>
                                        )}
                                    </div>
                                </div>

                                {/* Touch Numpad Grid */}
                                <div className="grid grid-cols-3 gap-2.5 mb-4">
                                    {['1', '2', '3', '4', '5', '6', '7', '8', '9'].map(digit => (
                                        <button
                                            key={digit}
                                            type="button"
                                            onClick={() => handleNumpadDigit(digit)}
                                            className="h-14 sm:h-16 rounded-2xl bg-slate-100/90 hover:bg-slate-200 active:bg-sky-600 active:text-white font-mono font-black text-2xl text-slate-800 flex items-center justify-center transition-all shadow-2xs"
                                        >
                                            {digit}
                                        </button>
                                    ))}

                                    <button
                                        type="button"
                                        onClick={handleNumpadClear}
                                        className="h-14 sm:h-16 rounded-2xl bg-rose-50 text-rose-600 hover:bg-rose-100 active:bg-rose-600 active:text-white font-extrabold text-base flex items-center justify-center transition-all"
                                    >
                                        AC
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => handleNumpadDigit('0')}
                                        className="h-14 sm:h-16 rounded-2xl bg-slate-100/90 hover:bg-slate-200 active:bg-sky-600 active:text-white font-mono font-black text-2xl text-slate-800 flex items-center justify-center transition-all shadow-2xs"
                                    >
                                        0
                                    </button>

                                    <button
                                        type="button"
                                        onClick={handleNumpadBackspace}
                                        className="h-14 sm:h-16 rounded-2xl bg-amber-50 text-amber-700 hover:bg-amber-100 active:bg-amber-600 active:text-white font-extrabold text-base flex items-center justify-center transition-all"
                                    >
                                        <Delete className="w-5 h-5" />
                                    </button>
                                </div>

                                {/* Confirm & Go To Menu Button */}
                                <button
                                    type="button"
                                    onClick={() => applyNumpadTable()}
                                    className="w-full py-3.5 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-sm sm:text-base shadow-md shadow-sky-600/25 flex items-center justify-center gap-2 transition-all active:scale-[0.98]"
                                >
                                    <span>Apply Table & Take Order</span>
                                    <ArrowRight className="w-4 h-4" />
                                </button>

                                {/* Quick Available Tables Tap Chips */}
                                <div className="mt-5 pt-4 border-t border-slate-100">
                                    <div className="text-[11px] font-extrabold uppercase text-slate-400 mb-2">
                                        Or Tap an Available Table:
                                    </div>
                                    <div className="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto">
                                        {allTablesList.filter(t => t.status === 'available').map(tbl => (
                                            <button
                                                key={tbl.id}
                                                type="button"
                                                onClick={() => applyNumpadTable(tbl)}
                                                className="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono font-extrabold text-xs hover:bg-emerald-100 transition-all"
                                            >
                                                T-{tbl.table_number}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* VIEW 3: ORDERS LEDGER & IRP 200D REPRINT */}
                    {activeTab === 'orders' && (
                        <div className="flex-1 flex flex-col overflow-hidden p-3 sm:p-4">
                            <div className="bg-white rounded-2xl border border-slate-200/80 flex-1 overflow-hidden flex flex-col shadow-2xs">
                                <div className="p-3 border-b border-slate-200/80 flex items-center justify-between gap-2">
                                    <div className="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            onClick={() => setOrdersFilter('all')}
                                            className={`px-3 py-1 rounded-lg text-xs font-bold transition-all ${
                                                ordersFilter === 'all' 
                                                    ? 'bg-slate-900 text-white' 
                                                    : 'text-slate-600 hover:bg-slate-100'
                                            }`}
                                        >
                                            All ({recentOrders.length})
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setOrdersFilter('mine')}
                                            className={`px-3 py-1 rounded-lg text-xs font-bold transition-all ${
                                                ordersFilter === 'mine' 
                                                    ? 'bg-slate-900 text-white' 
                                                    : 'text-slate-600 hover:bg-slate-100'
                                            }`}
                                        >
                                            Mine ({recentOrders.filter(o => o.waiter_id === user.id).length})
                                        </button>
                                    </div>

                                    <div className="text-xs text-slate-500 font-medium">
                                        Printer: <strong>IRP 200D</strong>
                                    </div>
                                </div>

                                <div className="flex-1 overflow-y-auto">
                                    <table className="w-full text-left text-xs border-collapse">
                                        <thead className="bg-slate-50 text-slate-500 font-extrabold uppercase tracking-wider sticky top-0 border-b border-slate-200">
                                            <tr>
                                                <th className="py-2.5 px-3 sm:px-4">Order #</th>
                                                <th className="py-2.5 px-3 sm:px-4">Table</th>
                                                <th className="py-2.5 px-3 sm:px-4">Total</th>
                                                <th className="py-2.5 px-3 sm:px-4 hidden sm:table-cell">Time</th>
                                                <th className="py-2.5 px-3 sm:px-4 text-right">Reprint</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 text-slate-700">
                                            {filteredOrders.length === 0 ? (
                                                <tr>
                                                    <td colSpan="5" className="py-10 text-center text-slate-400">
                                                        No orders placed yet.
                                                    </td>
                                                </tr>
                                            ) : (
                                                filteredOrders.map(order => (
                                                    <tr key={order.id} className="hover:bg-slate-50/80 transition-colors">
                                                        <td className="py-3 px-3 sm:px-4 font-mono font-bold text-sky-700">
                                                            {order.order_number}
                                                        </td>
                                                        <td className="py-3 px-3 sm:px-4 font-bold">
                                                            {order.table ? `T-${order.table.table_number}` : 'Takeaway'}
                                                        </td>
                                                        <td className="py-3 px-3 sm:px-4 font-mono font-extrabold text-slate-900">
                                                            ETB {parseFloat(order.total_amount).toFixed(2)}
                                                        </td>
                                                        <td className="py-3 px-3 sm:px-4 text-slate-400 font-mono hidden sm:table-cell">
                                                            {new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                        </td>
                                                        <td className="py-3 px-3 sm:px-4 text-right">
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
                                                                className="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] inline-flex items-center gap-1 transition-all"
                                                            >
                                                                <Printer className="w-3 h-3 text-slate-500" />
                                                                <span>IRP 200D</span>
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
                </main>
            </div>

            {/* 3. ORDER DISPATCH SUCCESS MODAL */}
            {successOrder && (
                <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in">
                    <div className="bg-white rounded-3xl max-w-sm sm:max-w-md w-full p-5 sm:p-6 shadow-2xl border border-slate-100 text-center">
                        <div className="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2.5">
                            <CheckCircle2 className="w-7 h-7" />
                        </div>

                        <h3 className="font-black text-lg sm:text-xl text-slate-800">
                            Order Dispatched
                        </h3>

                        <div className="font-mono font-black text-2xl text-sky-700 mt-1 mb-2">
                            {successOrder.order_number}
                        </div>

                        <div className="text-xs text-slate-500 mb-4">
                            {successOrder.table ? `Table: T-${successOrder.table.table_number}` : 'Takeaway'} • Total: ETB {parseFloat(successOrder.total_amount).toFixed(2)}
                        </div>

                        <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 text-left text-xs font-mono text-slate-600 mb-4 space-y-1">
                            <div className="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                <Printer className="w-3.5 h-3.5 text-emerald-600" />
                                <span>IRP 200D Tickets Printed:</span>
                            </div>
                            {successOrder.tickets && successOrder.tickets.map(ticket => (
                                <div key={ticket.id} className="text-emerald-700 font-semibold">
                                    ✔ {ticket.ticket_type === 'bar' ? 'Central Bar' : 'Main Kitchen'} Ticket #{ticket.ticket_number}
                                </div>
                            ))}
                        </div>

                        <button
                            type="button"
                            onClick={() => setSuccessOrder(null)}
                            className="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-sm rounded-xl shadow-xs transition-all"
                        >
                            Next Order
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
