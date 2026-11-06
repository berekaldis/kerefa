import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { 
    Utensils, LayoutGrid, Coffee, Beer, Wine, Flame, Clock, 
    Printer, Trash2, Plus, Minus, Search, CheckCircle2, 
    LogOut, AlertCircle, RefreshCw, X, ChevronRight, ShoppingBag, 
    Store, Bike, UserCheck, Star
} from 'lucide-react';
import { buildIrp200dTicketHtml, printIrp200d } from './thermalPrinter';

export default function PosApp({ initialData }) {
    // Initial State unpack
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
    const [activeTab, setActiveTab] = useState('menu'); // 'menu' | 'floor' | 'orders' | 'bestsellers' | 'search'
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [orderType, setOrderType] = useState('dine_in'); // 'dine_in' | 'takeaway' | 'delivery'
    const [selectedTable, setSelectedTable] = useState(null); // { id, table_number }
    const [cart, setCart] = useState([]);
    const [recentOrders, setRecentOrders] = useState(initialOrders);
    const [ordersFilter, setOrdersFilter] = useState('all'); // 'all' | 'mine'
    const [numpadValue, setNumpadValue] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [successOrder, setSuccessOrder] = useState(null);
    const [selectedOrderDetails, setSelectedOrderDetails] = useState(null);
    const [currentTime, setCurrentTime] = useState(new Date());

    // Live Clock
    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

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

    // Numpad handlers for table selection
    const handleNumpadPress = useCallback((val) => {
        setNumpadValue(prev => prev + val);
    }, []);

    const handleNumpadClear = useCallback(() => {
        setNumpadValue('');
    }, []);

    const handleNumpadBackspace = useCallback(() => {
        setNumpadValue(prev => prev.slice(0, -1));
    }, []);

    const handleNumpadApply = useCallback(() => {
        if (!numpadValue) return;
        let matched = null;
        diningAreas.forEach(area => {
            if (area.tables) {
                const found = area.tables.find(t => String(t.table_number) === String(numpadValue));
                if (found) matched = found;
            }
        });

        if (matched) {
            setSelectedTable({ id: matched.id, table_number: matched.table_number });
            setActiveTab('menu');
        } else {
            alert(`Table T-${numpadValue} was not found on this branch.`);
        }
        setNumpadValue('');
    }, [numpadValue, diningAreas]);

    // Submit Order & Print to IRP 200D
    const handleSubmitOrder = async () => {
        if (cart.length === 0) {
            alert('Cannot place an empty order. Please add menu items.');
            return;
        }

        if (orderType === 'dine_in' && !selectedTable) {
            alert('Please select a dining table for Dine-In orders.');
            setActiveTab('floor');
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
                // Refresh table status in dining areas
                diningAreas.forEach(area => {
                    if (area.tables) {
                        const t = area.tables.find(tbl => tbl.id === selectedTable.id);
                        if (t) {
                            t.status = 'occupied';
                            t.active_order = newOrder;
                        }
                    }
                });
                setSelectedTable(null);
            }

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
        <div className="flex flex-col h-screen w-screen overflow-hidden bg-slate-50 text-slate-800 font-sans">
            {/* 1. TOP HEADER (Smart & Light Brand Command Center) */}
            <header className="h-16 min-h-[64px] bg-white border-b border-slate-200/80 px-4 flex items-center justify-between shadow-xs z-30">
                {/* Brand & Location */}
                <div className="flex items-center gap-3 pr-4 border-r border-slate-200">
                    <div className="w-10 h-10 rounded-xl bg-linear-to-tr from-amber-600 to-amber-500 text-white flex items-center justify-center shadow-md shadow-amber-500/20">
                        <Utensils className="w-5 h-5" />
                    </div>
                    <div>
                        <div className="font-extrabold text-base tracking-tight text-slate-900 leading-tight">
                            {appName}
                        </div>
                        <div className="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>{branch.name}</span>
                        </div>
                    </div>
                </div>

                {/* System Status Badges */}
                <div className="hidden lg:flex items-center gap-3">
                    {/* Staff Profile */}
                    <div className="flex items-center gap-2 bg-slate-100/80 px-3 py-1.5 rounded-full border border-slate-200/60 text-xs">
                        <UserCheck className="w-3.5 h-3.5 text-sky-600" />
                        <span className="font-semibold text-slate-700">{user.name}</span>
                        <span className="bg-white px-1.5 py-0.5 rounded-md text-[10px] font-bold text-slate-500 uppercase">
                            {roleName}
                        </span>
                    </div>

                    {/* Printer Status */}
                    <div className="flex items-center gap-2 bg-emerald-50 text-emerald-800 px-3 py-1.5 rounded-full border border-emerald-200/80 text-xs font-medium">
                        <Printer className="w-3.5 h-3.5 text-emerald-600" />
                        <span className="font-mono text-[11px] font-semibold">IRP 200D (80mm) Online</span>
                    </div>

                    {/* Live Clock */}
                    <div className="flex items-center gap-2 bg-slate-100/80 px-3 py-1.5 rounded-full border border-slate-200/60 text-xs font-mono">
                        <Clock className="w-3.5 h-3.5 text-slate-500" />
                        <span className="font-bold text-slate-700">
                            {currentTime.toLocaleTimeString('en-US', { hour12: false })}
                        </span>
                        <span className="text-slate-400">
                            {currentTime.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}
                        </span>
                    </div>
                </div>

                {/* Navigation Tab Group */}
                <nav className="flex items-center gap-1 bg-slate-100/80 p-1 rounded-xl border border-slate-200/60">
                    <button 
                        onClick={() => setActiveTab('menu')}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'menu' 
                                ? 'bg-white text-sky-700 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                        }`}
                    >
                        <LayoutGrid className="w-3.5 h-3.5" />
                        <span>Menu</span>
                    </button>

                    <button 
                        onClick={() => setActiveTab('floor')}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'floor' 
                                ? 'bg-white text-sky-700 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                        }`}
                    >
                        <Store className="w-3.5 h-3.5" />
                        <span>Floor & Tables</span>
                        {selectedTable && (
                            <span className="bg-sky-100 text-sky-700 px-1 rounded-sm text-[10px] font-mono">
                                T{selectedTable.table_number}
                            </span>
                        )}
                    </button>

                    <button 
                        onClick={() => setActiveTab('orders')}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'orders' 
                                ? 'bg-white text-sky-700 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                        }`}
                    >
                        <Clock className="w-3.5 h-3.5" />
                        <span>Orders</span>
                        <span className="bg-slate-200 text-slate-700 px-1.5 py-0.2 rounded-full text-[10px] font-mono">
                            {recentOrders.length}
                        </span>
                    </button>

                    <button 
                        onClick={() => setActiveTab('bestsellers')}
                        className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${
                            activeTab === 'bestsellers' 
                                ? 'bg-white text-amber-600 shadow-xs' 
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/60'
                        }`}
                    >
                        <Star className="w-3.5 h-3.5 text-amber-500 fill-amber-500" />
                        <span>Bestsellers</span>
                    </button>

                    <a 
                        href={routes.dashboard || '/dashboard'}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 transition-all ml-1"
                        title="Return to ERP Dashboard"
                    >
                        <LogOut className="w-3.5 h-3.5" />
                        <span>Exit</span>
                    </a>
                </nav>
            </header>

            {/* 2. MAIN APPLICATION WORKSPACE */}
            <div className="flex flex-1 overflow-hidden">
                {/* LEFT: Order Docket / Cart (Width: 380px) */}
                <aside className="w-[390px] min-w-[340px] max-w-[430px] bg-white border-r border-slate-200/80 flex flex-col h-full shadow-sm z-20">
                    {/* Docket Context Controls */}
                    <div className="p-3 border-b border-slate-100 bg-slate-50/60 flex flex-col gap-2.5">
                        {/* Order Type Pill Selector */}
                        <div className="grid grid-cols-3 gap-1.5 bg-slate-200/60 p-1 rounded-xl">
                            <button
                                type="button"
                                onClick={() => setOrderType('dine_in')}
                                className={`flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                    orderType === 'dine_in' 
                                        ? 'bg-white text-sky-700 shadow-xs' 
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <Store className="w-3.5 h-3.5" />
                                <span>Dine-In</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setOrderType('takeaway');
                                    setSelectedTable(null);
                                }}
                                className={`flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                    orderType === 'takeaway' 
                                        ? 'bg-white text-sky-700 shadow-xs' 
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <ShoppingBag className="w-3.5 h-3.5" />
                                <span>Takeaway</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setOrderType('delivery');
                                    setSelectedTable(null);
                                }}
                                className={`flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-bold transition-all ${
                                    orderType === 'delivery' 
                                        ? 'bg-white text-sky-700 shadow-xs' 
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <Bike className="w-3.5 h-3.5" />
                                <span>Delivery</span>
                            </button>
                        </div>

                        {/* Table Selector Bar */}
                        <div className="flex items-center justify-between">
                            {orderType === 'dine_in' ? (
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('floor')}
                                    className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:border-sky-400 hover:text-sky-700 transition-all shadow-xs"
                                >
                                    <Store className="w-3.5 h-3.5 text-amber-500" />
                                    <span>{selectedTable ? `Table: T-${selectedTable.table_number}` : 'Select Table'}</span>
                                    <ChevronRight className="w-3.5 h-3.5 text-slate-400" />
                                </button>
                            ) : (
                                <span className="text-xs font-semibold text-slate-500 px-2 py-1 bg-slate-100 rounded-md">
                                    Direct Counter Order
                                </span>
                            )}

                            <span className="font-mono text-xs font-bold text-slate-500">
                                {cartTotals.itemCount} {cartTotals.itemCount === 1 ? 'item' : 'items'}
                            </span>
                        </div>
                    </div>

                    {/* Cart Items Table Header */}
                    <div className="grid grid-cols-[1fr_60px_65px_75px] px-4 py-2 bg-slate-100/60 border-b border-slate-200/80 text-[11px] font-extrabold uppercase text-slate-500 tracking-wider">
                        <div>Item & Station</div>
                        <div className="text-center">Qty</div>
                        <div className="text-right">Price</div>
                        <div className="text-right">Total</div>
                    </div>

                    {/* Cart Scrollable Items List */}
                    <div className="flex-1 overflow-y-auto p-3 space-y-2">
                        {cart.length === 0 ? (
                            <div className="h-full flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                                <div className="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                                    <ShoppingBag className="w-7 h-7" />
                                </div>
                                <div className="font-bold text-slate-600 text-sm">Order Docket is Empty</div>
                                <div className="text-xs text-slate-400 mt-1 max-w-[200px]">
                                    Select food or drinks from the menu to build the order
                                </div>
                            </div>
                        ) : (
                            cart.map(item => (
                                <div 
                                    key={item.id}
                                    className="p-2.5 rounded-xl border border-slate-200/90 bg-white hover:border-slate-300 transition-all shadow-2xs"
                                >
                                    <div className="grid grid-cols-[1fr_60px_65px_75px] items-center">
                                        {/* Item Name & Station Badge */}
                                        <div className="pr-1">
                                            <div className="font-bold text-sm text-slate-800 leading-snug line-clamp-1">
                                                {item.name}
                                            </div>
                                            <span className={`inline-block text-[10px] font-extrabold px-1.5 py-0.5 rounded-md mt-1 ${
                                                item.stationType === 'bar' 
                                                    ? 'bg-sky-50 text-sky-700 border border-sky-200/80' 
                                                    : 'bg-rose-50 text-rose-700 border border-rose-200/80'
                                            }`}>
                                                {item.stationName}
                                            </span>
                                        </div>

                                        {/* Qty Steppers */}
                                        <div className="flex items-center justify-center gap-1 bg-slate-100 p-1 rounded-lg">
                                            <button 
                                                type="button"
                                                onClick={() => updateQuantity(item.id, -1)}
                                                className="w-4.5 h-4.5 rounded bg-white text-slate-600 hover:bg-slate-200 flex items-center justify-center text-xs font-bold transition-all shadow-2xs"
                                            >
                                                <Minus className="w-3 h-3" />
                                            </button>
                                            <span className="font-mono text-xs font-black min-w-[14px] text-center">
                                                {item.quantity}
                                            </span>
                                            <button 
                                                type="button"
                                                onClick={() => updateQuantity(item.id, 1)}
                                                className="w-4.5 h-4.5 rounded bg-white text-slate-600 hover:bg-slate-200 flex items-center justify-center text-xs font-bold transition-all shadow-2xs"
                                            >
                                                <Plus className="w-3 h-3" />
                                            </button>
                                        </div>

                                        {/* Unit Price */}
                                        <div className="text-right font-mono text-xs text-slate-500 font-medium">
                                            {item.price.toFixed(2)}
                                        </div>

                                        {/* Line Total */}
                                        <div className="text-right font-mono text-xs font-extrabold text-slate-800">
                                            {(item.price * item.quantity).toFixed(2)}
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Cart Totals & Primary Actions Footer */}
                    <div className="p-4 bg-white border-t border-slate-200/80 space-y-3 shadow-xs">
                        <div className="flex items-baseline justify-between">
                            <div>
                                <span className="text-[11px] font-extrabold uppercase text-slate-400 tracking-wider">
                                    Grand Total
                                </span>
                                <div className="text-[11px] text-slate-500">VAT & Taxes Included</div>
                            </div>
                            <div className="font-mono font-black text-2xl text-slate-900 tracking-tight">
                                <span className="text-sm font-bold text-slate-400 mr-1">ETB</span>
                                {cartTotals.grandTotal.toFixed(2)}
                            </div>
                        </div>

                        {/* Action Buttons: Void (F7) & Order (F6) */}
                        <div className="grid grid-cols-[90px_1fr] gap-2 pt-1">
                            <button
                                type="button"
                                onClick={clearCart}
                                disabled={cart.length === 0}
                                className="flex flex-col items-center justify-center py-2.5 rounded-xl border border-rose-200 text-rose-600 bg-rose-50/70 hover:bg-rose-100/90 font-extrabold text-xs transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                                title="Void Order Docket (F7)"
                            >
                                <Trash2 className="w-4 h-4 mb-0.5" />
                                <span>Void (F7)</span>
                            </button>

                            <button
                                type="button"
                                onClick={handleSubmitOrder}
                                disabled={cart.length === 0 || isSubmitting}
                                className="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-linear-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 text-white font-extrabold text-sm shadow-md shadow-emerald-500/25 transition-all disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98]"
                                title="Send Order to Kitchen & Bar IRP 200D Printers (F6)"
                            >
                                {isSubmitting ? (
                                    <>
                                        <RefreshCw className="w-4 h-4 animate-spin" />
                                        <span>Dispatching...</span>
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

                {/* RIGHT: Dynamic Views (Menu, Floor, Orders, Bestsellers) */}
                <main className="flex-1 bg-slate-100/60 overflow-hidden flex flex-col">
                    {/* TAB VIEW 1: MENU & CATEGORIES */}
                    {activeTab === 'menu' && (
                        <div className="flex-1 flex flex-col h-full overflow-hidden">
                            {/* Categories Filter Bar & Search */}
                            <div className="p-4 bg-white border-b border-slate-200/80 flex items-center justify-between gap-4">
                                <div className="flex items-center gap-2 overflow-x-auto pb-1 max-w-[70%]">
                                    <button
                                        type="button"
                                        onClick={() => setSelectedCategory('all')}
                                        className={`px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all ${
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
                                            className={`px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all ${
                                                selectedCategory === cat.id
                                                    ? 'bg-sky-600 text-white shadow-xs'
                                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                            }`}
                                        >
                                            {cat.name} ({cat.menu_items ? cat.menu_items.length : 0})
                                        </button>
                                    ))}
                                </div>

                                {/* Quick Search Bar */}
                                <div className="relative w-64">
                                    <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input 
                                        type="text" 
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="Search dishes or code..." 
                                        className="w-full pl-9 pr-8 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-sky-500 transition-all"
                                    />
                                    {searchQuery && (
                                        <button 
                                            onClick={() => setSearchQuery('')}
                                            className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        >
                                            <X className="w-3.5 h-3.5" />
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Products Grid */}
                            <div className="flex-1 overflow-y-auto p-4">
                                <div className="grid grid-cols-[repeat(auto-fill,minmax(190px,1fr))] gap-3">
                                    {displayedItems.map(item => {
                                        const stationType = item.order_station ? item.order_station.station_type : (item.preparation_department || 'kitchen');
                                        const stationName = item.order_station ? item.order_station.name : (stationType === 'bar' ? 'Bar Station' : 'Kitchen Station');

                                        return (
                                            <div 
                                                key={item.id}
                                                onClick={() => addToCart(item)}
                                                className="group p-3.5 rounded-2xl bg-white border border-slate-200/90 hover:border-sky-300 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 cursor-pointer flex flex-col justify-between min-h-[125px]"
                                            >
                                                <div>
                                                    <div className="font-extrabold text-sm text-slate-800 leading-snug group-hover:text-sky-700 transition-colors">
                                                        {item.name}
                                                    </div>
                                                    <div className="flex items-center gap-1.5 mt-1.5">
                                                        <span className={`text-[10px] font-extrabold px-1.5 py-0.5 rounded-md ${
                                                            stationType === 'bar' 
                                                                ? 'bg-sky-50 text-sky-700 border border-sky-200/80' 
                                                                : 'bg-rose-50 text-rose-700 border border-rose-200/80'
                                                        }`}>
                                                            {stationName}
                                                        </span>
                                                        <span className="text-[10px] font-mono text-slate-400">
                                                            {item.code || ''}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div className="flex items-baseline justify-between mt-3 pt-2 border-t border-slate-100">
                                                    <div className="font-mono font-black text-sm text-emerald-700">
                                                        <span className="text-[10px] font-bold text-slate-400 mr-0.5">ETB</span>
                                                        {parseFloat(item.price).toFixed(2)}
                                                    </div>
                                                    <div className="w-6 h-6 rounded-lg bg-slate-100 group-hover:bg-sky-600 group-hover:text-white text-slate-500 flex items-center justify-center transition-colors">
                                                        <Plus className="w-3.5 h-3.5" />
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* TAB VIEW 2: FLOOR & TABLES (RMS) */}
                    {activeTab === 'floor' && (
                        <div className="flex-1 flex overflow-hidden p-4 gap-4">
                            {/* Left: Interactive Dining Areas */}
                            <div className="flex-1 bg-white rounded-2xl border border-slate-200/80 p-4 overflow-y-auto shadow-2xs">
                                <div className="space-y-6">
                                    {diningAreas.map(area => (
                                        <div key={area.id}>
                                            <div className="flex items-center justify-between mb-2.5 pb-1 border-b border-slate-100">
                                                <h3 className="font-extrabold text-xs uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                                                    <Store className="w-3.5 h-3.5 text-amber-500" />
                                                    <span>{area.name}</span>
                                                </h3>
                                                <span className="text-xs font-semibold text-slate-400">
                                                    {area.tables ? area.tables.length : 0} Tables
                                                </span>
                                            </div>

                                            <div className="grid grid-cols-[repeat(auto-fill,minmax(120px,1fr))] gap-2.5">
                                                {area.tables && area.tables.map(table => {
                                                    const isAvailable = table.status === 'available';
                                                    const isSelected = selectedTable && selectedTable.id === table.id;

                                                    return (
                                                        <button
                                                            key={table.id}
                                                            type="button"
                                                            onClick={() => {
                                                                setSelectedTable({ id: table.id, table_number: table.table_number });
                                                                setActiveTab('menu');
                                                            }}
                                                            className={`p-3 rounded-xl border text-left transition-all flex flex-col justify-between min-h-[85px] ${
                                                                isSelected
                                                                    ? 'bg-sky-50 border-sky-500 ring-2 ring-sky-500/20 shadow-xs'
                                                                    : isAvailable
                                                                        ? 'bg-emerald-50/50 border-emerald-200/80 hover:bg-emerald-50 hover:border-emerald-300'
                                                                        : 'bg-rose-50/50 border-rose-200/80 hover:bg-rose-50'
                                                            }`}
                                                        >
                                                            <div className="flex items-center justify-between">
                                                                <span className="font-mono font-black text-base text-slate-800">
                                                                    T-{table.table_number}
                                                                </span>
                                                                <span className={`text-[10px] font-extrabold px-1.5 py-0.2 rounded-md ${
                                                                    isAvailable ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                                                }`}>
                                                                    {isAvailable ? 'Free' : 'Busy'}
                                                                </span>
                                                            </div>
                                                            <div className="text-[11px] text-slate-500 font-medium">
                                                                {table.capacity} Guests
                                                                {table.active_order && (
                                                                    <div className="text-[10px] font-mono text-amber-700 font-bold truncate">
                                                                        #{table.active_order.order_number}
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Right: Modern Touch Numpad */}
                            <div className="w-80 bg-white rounded-2xl border border-slate-200/80 p-4 flex flex-col justify-between shadow-2xs">
                                <div>
                                    <div className="text-xs font-extrabold uppercase text-slate-400 tracking-wider mb-2">
                                        Type Table Number
                                    </div>
                                    <div className="bg-slate-50 border border-slate-200 rounded-xl p-3 text-right">
                                        <div className="font-mono font-black text-3xl text-slate-800">
                                            {numpadValue || '—'}
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 my-4 flex-1">
                                    {['1','2','3','4','5','6','7','8','9'].map(num => (
                                        <button
                                            key={num}
                                            type="button"
                                            onClick={() => handleNumpadPress(num)}
                                            className="rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-sky-600 active:text-white font-mono font-extrabold text-xl text-slate-800 flex items-center justify-center transition-all shadow-2xs"
                                        >
                                            {num}
                                        </button>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={handleNumpadClear}
                                        className="rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-sm flex items-center justify-center transition-all"
                                    >
                                        AC
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => handleNumpadPress('0')}
                                        className="rounded-xl bg-slate-100 hover:bg-slate-200 font-mono font-extrabold text-xl text-slate-800 flex items-center justify-center transition-all"
                                    >
                                        0
                                    </button>
                                    <button
                                        type="button"
                                        onClick={handleNumpadBackspace}
                                        className="rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 font-bold text-sm flex items-center justify-center transition-all"
                                    >
                                        C
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    onClick={handleNumpadApply}
                                    className="w-full py-3 bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-sm rounded-xl shadow-xs transition-all"
                                >
                                    Select Table
                                </button>
                            </div>
                        </div>
                    )}

                    {/* TAB VIEW 3: ORDERS TRACKING & IRP 200D REPRINT */}
                    {activeTab === 'orders' && (
                        <div className="flex-1 flex flex-col overflow-hidden p-4">
                            <div className="bg-white rounded-2xl border border-slate-200/80 flex-1 overflow-hidden flex flex-col shadow-2xs">
                                <div className="p-3 border-b border-slate-200/80 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={() => setOrdersFilter('all')}
                                            className={`px-3 py-1 rounded-lg text-xs font-bold transition-all ${
                                                ordersFilter === 'all' 
                                                    ? 'bg-slate-900 text-white' 
                                                    : 'text-slate-600 hover:bg-slate-100'
                                            }`}
                                        >
                                            All Branch Orders ({recentOrders.length})
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
                                            My Orders ({recentOrders.filter(o => o.waiter_id === user.id).length})
                                        </button>
                                    </div>

                                    <div className="text-xs text-slate-500 font-medium">
                                        Thermal Order Printer Model: <strong>IRP 200D</strong>
                                    </div>
                                </div>

                                <div className="flex-1 overflow-y-auto">
                                    <table className="w-full text-left text-xs border-collapse">
                                        <thead className="bg-slate-50 text-slate-500 font-extrabold uppercase tracking-wider sticky top-0 border-b border-slate-200">
                                            <tr>
                                                <th className="py-2.5 px-4">Order #</th>
                                                <th className="py-2.5 px-4">Type</th>
                                                <th className="py-2.5 px-4">Table</th>
                                                <th className="py-2.5 px-4">Waiter</th>
                                                <th className="py-2.5 px-4">Total</th>
                                                <th className="py-2.5 px-4">Time</th>
                                                <th className="py-2.5 px-4">Station Tickets</th>
                                                <th className="py-2.5 px-4 text-right">Reprint</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 text-slate-700">
                                            {filteredOrders.length === 0 ? (
                                                <tr>
                                                    <td colSpan="8" className="py-10 text-center text-slate-400">
                                                        No orders placed yet.
                                                    </td>
                                                </tr>
                                            ) : (
                                                filteredOrders.map(order => (
                                                    <tr key={order.id} className="hover:bg-slate-50/80 transition-colors">
                                                        <td className="py-3 px-4 font-mono font-bold text-sky-700">
                                                            {order.order_number}
                                                        </td>
                                                        <td className="py-3 px-4 capitalize font-medium">
                                                            {order.order_type}
                                                        </td>
                                                        <td className="py-3 px-4 font-bold">
                                                            {order.table ? `T-${order.table.table_number}` : 'Takeaway'}
                                                        </td>
                                                        <td className="py-3 px-4">
                                                            {order.waiter ? order.waiter.name : 'Staff'}
                                                        </td>
                                                        <td className="py-3 px-4 font-mono font-extrabold text-slate-900">
                                                            ETB {parseFloat(order.total_amount).toFixed(2)}
                                                        </td>
                                                        <td className="py-3 px-4 text-slate-400 font-mono">
                                                            {new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                                        </td>
                                                        <td className="py-3 px-4">
                                                            <div className="flex items-center gap-1">
                                                                {order.tickets && order.tickets.map(t => (
                                                                    <span 
                                                                        key={t.id}
                                                                        className={`px-1.5 py-0.5 rounded text-[10px] font-bold ${
                                                                            t.ticket_type === 'bar' 
                                                                                ? 'bg-sky-50 text-sky-700 border border-sky-200' 
                                                                                : 'bg-rose-50 text-rose-700 border border-rose-200'
                                                                        }`}
                                                                    >
                                                                        {t.ticket_type === 'bar' ? 'Bar' : 'Kitchen'} #{t.ticket_number}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </td>
                                                        <td className="py-3 px-4 text-right">
                                                            <button
                                                                type="button"
                                                                onClick={() => {
                                                                    if (order.tickets && order.tickets.length > 0) {
                                                                        order.tickets.forEach(ticket => {
                                                                            const html = buildIrp200dTicketHtml(order, ticket, appName);
                                                                            printIrp200d(html);
                                                                        });
                                                                    } else {
                                                                        alert('No station tickets associated with this order.');
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

                    {/* TAB VIEW 4: BESTSELLERS / RUSH HOUR */}
                    {activeTab === 'bestsellers' && (
                        <div className="flex-1 flex flex-col overflow-hidden p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <div>
                                    <h2 className="font-extrabold text-base text-slate-800">
                                        High-Volume Speed Ordering
                                    </h2>
                                    <p className="text-xs text-slate-500">
                                        One-touch quick item addition for rapid restaurant rush service
                                    </p>
                                </div>
                            </div>

                            <div className="flex-1 overflow-y-auto">
                                <div className="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-3">
                                    {allMenuItems.slice(0, 16).map(item => (
                                        <div
                                            key={item.id}
                                            onClick={() => addToCart(item)}
                                            className="p-4 rounded-2xl bg-white border border-slate-200/90 hover:border-amber-400 hover:shadow-md hover:-translate-y-0.5 transition-all duration-150 cursor-pointer flex flex-col justify-between min-h-[130px]"
                                        >
                                            <div>
                                                <div className="font-extrabold text-sm text-slate-800 leading-snug">
                                                    {item.name}
                                                </div>
                                                <span className="inline-block text-[10px] font-extrabold px-1.5 py-0.5 rounded-md mt-1 bg-amber-50 text-amber-700 border border-amber-200">
                                                    Bestseller
                                                </span>
                                            </div>

                                            <div className="flex items-baseline justify-between mt-3 pt-2 border-t border-slate-100">
                                                <div className="font-mono font-black text-sm text-emerald-700">
                                                    ETB {parseFloat(item.price).toFixed(2)}
                                                </div>
                                                <div className="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center shadow-xs">
                                                    <Plus className="w-3.5 h-3.5" />
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </main>
            </div>

            {/* 3. ORDER DISPATCH SUCCESS MODAL */}
            {successOrder && (
                <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in">
                    <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 text-center">
                        <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                            <CheckCircle2 className="w-8 h-8" />
                        </div>

                        <h3 className="font-black text-xl text-slate-800">
                            Order Dispatched to Stations
                        </h3>

                        <div className="font-mono font-black text-2xl text-sky-700 mt-1 mb-2">
                            {successOrder.order_number}
                        </div>

                        <div className="text-xs text-slate-500 mb-4">
                            {successOrder.table ? `Table: T-${successOrder.table.table_number}` : 'Takeaway Order'} • Total: ETB {parseFloat(successOrder.total_amount).toFixed(2)}
                        </div>

                        <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 text-left text-xs font-mono text-slate-600 mb-5 space-y-1">
                            <div className="font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                <Printer className="w-3.5 h-3.5 text-emerald-600" />
                                <span>Thermal Order Tickets (IRP 200D):</span>
                            </div>
                            {successOrder.tickets && successOrder.tickets.map(ticket => (
                                <div key={ticket.id} className="text-emerald-700 font-semibold">
                                    ✔ {ticket.ticket_type === 'bar' ? 'Central Bar' : 'Main Kitchen'} Ticket #{ticket.ticket_number} (Printed)
                                </div>
                            ))}
                        </div>

                        <button
                            type="button"
                            onClick={() => setSuccessOrder(null)}
                            className="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-sm rounded-xl shadow-xs transition-all"
                        >
                            Ready for Next Order
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
