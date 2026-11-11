import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { buildIrp200dTicketHtml, buildIrp200dSettlementReceiptHtml, printIrp200d } from './thermalPrinter';

// Audio Synthesizer for Tactile POS Feedback
function playPosSound(type = 'add') {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

        if (type === 'add') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.06);
            gain.gain.setValueAtTime(0.06, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.14);
            osc.start();
            osc.stop(ctx.currentTime + 0.14);
        } else if (type === 'success') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(523.25, ctx.currentTime);
            osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.08);
            osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.16);
            gain.gain.setValueAtTime(0.09, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.32);
            osc.start();
            osc.stop(ctx.currentTime + 0.32);
        } else if (type === 'void') {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(260, ctx.currentTime);
            gain.gain.setValueAtTime(0.06, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
            osc.start();
            osc.stop(ctx.currentTime + 0.12);
        }
    } catch {
        // Audio muted or not permitted
    }
}

export default function PosApp({ initialData }) {
    // Initial Data
    const branch = initialData.branch || { id: 1, name: 'Main Branch' };
    const user = initialData.user || { id: 1, name: 'Cashier' };
    const roleName = initialData.role || 'Staff';
    const categories = initialData.categories || [];
    const diningAreas = initialData.diningAreas || [];
    const initialOrders = initialData.recentOrders || [];
    const waitersList = initialData.waiters || [];
    const routes = initialData.routes || {};
    const csrfToken = initialData.csrfToken || '';
    const appName = initialData.appName || 'Kerefa Restaurant';

    // Access Control & Permission Verifier
    const permissions = useMemo(() => initialData.permissions || ['*'], [initialData.permissions]);
    const can = useCallback((perm) => {
        if (!permissions || permissions.length === 0) return true;
        if (permissions.includes('*')) return true;
        return permissions.includes(perm);
    }, [permissions]);

    // Top Navigation Tabs (Matches Reference POS Layout Exactly)
    // 'CATEGORY' | 'RMS' | 'REFERENCE' | 'FAVORITE' | 'SEARCH' | 'EXIT'
    const [activeTopTab, setActiveTopTab] = useState('CATEGORY');

    // RMS Sub-Navigation Tabs: 'TABLE' | 'WAITERS' | 'COVER' | 'ROOM CHARGE'
    const [rmsSubTab, setRmsSubTab] = useState('TABLE');

    // REFERENCE Sub-Navigation Tabs: 'Orders' | 'BILL SETTLEMENT'
    const [referenceSubTab, setReferenceSubTab] = useState('Orders');
    const [selectedReferenceOrder, setSelectedReferenceOrder] = useState(null);
    const [referenceFilter, setReferenceFilter] = useState('all'); // 'all' | 'mine'

    // Category / Item Navigation
    const [selectedCategory, setSelectedCategory] = useState(null); // null = categories grid, object = items
    const [searchQuery, setSearchQuery] = useState('');

    // Order Specifications
    const [orderType, setOrderType] = useState('dine_in');
    const [selectedTable, setSelectedTable] = useState(null); // { id, table_number, area_name }
    const [selectedWaiter, setSelectedWaiter] = useState(() => {
        return waitersList.find(w => w.id === user.id) || waitersList[0] || user;
    });
    const [coverCount, setCoverCount] = useState(1);
    const [roomCharge, setRoomCharge] = useState('');

    // Cart / Docket State
    // Items structure: { id, name, code, price, quantity, stationType, stationName, notes, isExistingItem: bool }
    const [cart, setCart] = useState([]);
    const [selectedCartItemId, setSelectedCartItemId] = useState(null);

    // Active Held Order Editing State (when adding products to a held order)
    const [editingOrder, setEditingOrder] = useState(null); // null or Order object

    // Orders History State
    const [recentOrders, setRecentOrders] = useState(initialOrders);

    // Modals & Dialogs
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [successOrder, setSuccessOrder] = useState(null);
    const [showRetrieveModal, setShowRetrieveModal] = useState(false);
    const [showSettleModal, setShowSettleModal] = useState(false);
    const [orderToSettle, setOrderToSettle] = useState(null);
    const [settlePaymentMethod, setSettlePaymentMethod] = useState('cash'); // 'cash' | 'telebirr' | 'cbe_birr' | 'card' | 'room_charge'
    const [settleAmountTendered, setSettleAmountTendered] = useState('');
    const [printReceiptOnSettle, setPrintReceiptOnSettle] = useState(true);
    const [showExitModal, setShowExitModal] = useState(false);
    const [itemNotesModal, setItemNotesModal] = useState(null); // { itemId, name, notes }
    const [toasts, setToasts] = useState([]);
    const [currentTime, setCurrentTime] = useState(new Date());

    // POS Theme Mode ('light' | 'dark')
    const [theme, setTheme] = useState(() => {
        try {
            return localStorage.getItem('kerefa_pos_theme') || localStorage.getItem('kerefa_erp_theme') || 'light';
        } catch {
            return 'light';
        }
    });

    const toggleTheme = useCallback(() => {
        setTheme(prev => {
            const next = prev === 'dark' ? 'light' : 'dark';
            try {
                localStorage.setItem('kerefa_pos_theme', next);
                localStorage.setItem('kerefa_erp_theme', next);
                document.documentElement.setAttribute('data-bs-theme', next);
            } catch {
                // Ignore storage error
            }
            return next;
        });
    }, []);

    useEffect(() => {
        try {
            document.documentElement.setAttribute('data-bs-theme', theme);
        } catch {
            // Ignore DOM error
        }
    }, [theme]);

    // Responsive Mobile View Switcher ('content' | 'docket')
    const [mobileView, setMobileView] = useState('content');

    // Live Digital Clock & Date
    useEffect(() => {
        const timer = setInterval(() => setCurrentTime(new Date()), 1000);
        return () => clearInterval(timer);
    }, []);

    // Floating Toast Notification Dispatcher
    const addToast = useCallback((title, message, type = 'success') => {
        const id = Date.now() + Math.random();
        setToasts(prev => [...prev, { id, title, message, type }]);
        setTimeout(() => {
            setToasts(prev => prev.filter(t => t.id !== id));
        }, 2600);
    }, []);

    const removeToast = useCallback((id) => {
        setToasts(prev => prev.filter(t => t.id !== id));
    }, []);

    // Flatten all tables across dining areas
    const allTablesList = useMemo(() => {
        const list = [];
        diningAreas.forEach(area => {
            if (area.tables && Array.isArray(area.tables)) {
                area.tables.forEach(tbl => {
                    list.push({
                        ...tbl,
                        area_name: area.name,
                        dining_area_id: area.id
                    });
                });
            }
        });
        return list;
    }, [diningAreas]);

    // Area filter for Table Seating Plan
    const [selectedAreaId, setSelectedAreaId] = useState('all');
    const [tableSearchFilter, setTableSearchFilter] = useState('');

    const filteredTablesList = useMemo(() => {
        let list = allTablesList;
        if (selectedAreaId !== 'all') {
            list = list.filter(t => t.dining_area_id === selectedAreaId);
        }
        if (tableSearchFilter.trim()) {
            const q = tableSearchFilter.trim().toLowerCase();
            list = list.filter(t => 
                String(t.table_number).toLowerCase().includes(q) ||
                (t.area_name && t.area_name.toLowerCase().includes(q))
            );
        }
        return list;
    }, [allTablesList, selectedAreaId, tableSearchFilter]);

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

    // Items for current category or search
    const displayedItems = useMemo(() => {
        if (activeTopTab === 'FAVORITE') {
            return allMenuItems.slice(0, 16);
        }
        if (activeTopTab === 'SEARCH' || searchQuery.trim()) {
            const q = searchQuery.toLowerCase().trim();
            return allMenuItems.filter(it => 
                it.name.toLowerCase().includes(q) || 
                (it.code && it.code.toLowerCase().includes(q)) ||
                (it.category_name && it.category_name.toLowerCase().includes(q))
            );
        }
        if (selectedCategory) {
            return allMenuItems.filter(it => it.category_id === selectedCategory.id);
        }
        return [];
    }, [allMenuItems, activeTopTab, searchQuery, selectedCategory]);

    // Filtered Waiters List from HR Waiter Department
    const [waiterSearchQuery, setWaiterSearchQuery] = useState('');
    const filteredWaiters = useMemo(() => {
        if (!waiterSearchQuery.trim()) return waitersList;
        const q = waiterSearchQuery.toLowerCase().trim();
        return waitersList.filter(w => 
            w.name.toLowerCase().includes(q) || 
            (w.username && w.username.toLowerCase().includes(q))
        );
    }, [waitersList, waiterSearchQuery]);

    // Cart calculations (Grand Total in Ethiopian Birr)
    const cartTotals = useMemo(() => {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
        const newItemsCount = cart.filter(it => !it.isExistingItem).reduce((sum, it) => sum + it.quantity, 0);
        return {
            subtotal,
            grandTotal: subtotal,
            itemCount,
            newItemsCount
        };
    }, [cart]);

    // Add item to cart
    const addToCart = useCallback((item) => {
        playPosSound('add');
        setCart(prev => {
            // Find existing non-submitted item with same ID
            const existingIndex = prev.findIndex(ci => ci.id === item.id && !ci.isExistingItem);
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
                notes: '',
                isExistingItem: false
            }];
        });
        setSelectedCartItemId(item.id);
        addToast('Item Added', `${item.name} added to docket`, 'success');
    }, [addToast]);

    // Update quantity
    const updateQuantity = useCallback((itemId, delta) => {
        playPosSound('add');
        setCart(prev => {
            return prev.map(item => {
                if (item.id === itemId) {
                    if (item.isExistingItem) {
                        addToast('Notice', 'Cannot modify quantity of already sent items. Add new line instead.', 'warning');
                        return item;
                    }
                    const newQty = item.quantity + delta;
                    return newQty > 0 ? { ...item, quantity: newQty } : null;
                }
                return item;
            }).filter(Boolean);
        });
        setSelectedCartItemId(itemId);
    }, [addToast]);

    // VOID ACTION: Removes ONLY the selected line item!
    const voidSelectedItem = useCallback(() => {
        if (cart.length === 0) {
            addToast('Docket Empty', 'No items in order to void.', 'warning');
            return;
        }

        const targetId = selectedCartItemId || cart[cart.length - 1]?.id;
        const targetItem = cart.find(it => it.id === targetId);

        if (!targetItem) {
            addToast('No Selection', 'Tap an item in the docket to void.', 'warning');
            return;
        }

        if (targetItem.isExistingItem) {
            addToast('Void Restricted', 'Item is already dispatched to kitchen/bar. Contact supervisor to cancel.', 'warning');
            return;
        }

        playPosSound('void');
        setCart(prev => {
            const updated = prev.filter(it => it.id !== targetId || it.isExistingItem);
            setSelectedCartItemId(updated.length > 0 ? updated[updated.length - 1].id : null);
            return updated;
        });

        addToast('Item Voided', `${targetItem.name} removed from docket`, 'danger');
    }, [cart, selectedCartItemId, addToast]);

    // Save notes for item
    const saveItemNotes = useCallback((itemId, notes) => {
        setCart(prev => prev.map(it => it.id === itemId ? { ...it, notes } : it));
        setItemNotesModal(null);
        addToast('Notes Saved', 'Kitchen / Bar prep notes saved', 'info');
    }, [addToast]);

    // Select Table Action
    const handleSelectTable = useCallback((table) => {
        // If table has an active order, prompt to retrieve/add products or settle
        if (table.active_order) {
            setSelectedReferenceOrder(table.active_order);
            setShowRetrieveModal(true);
            return;
        }

        setSelectedTable({
            id: table.id,
            table_number: table.table_number,
            area_name: table.area_name || ''
        });
        setOrderType('dine_in');
        addToast('Table Selected', `Table T-${table.table_number} assigned`, 'info');
        setActiveTopTab('CATEGORY');
    }, [addToast]);

    // Select Waiter Action (From HR Waiter Department)
    const handleSelectWaiter = useCallback((waiter) => {
        setSelectedWaiter(waiter);
        addToast('Waiter Assigned', `Waiter: ${waiter.name}`, 'info');
        setActiveTopTab('CATEGORY');
    }, [addToast]);

    // RETRIEVE / LOAD HELD ORDER TO ADD PRODUCTS
    const handleLoadOrderToEdit = useCallback((order) => {
        if (!order) return;
        setEditingOrder(order);

        // Prepopulate table
        if (order.table) {
            setSelectedTable({
                id: order.table.id,
                table_number: order.table.table_number,
                area_name: order.table.dining_area ? order.table.dining_area.name : ''
            });
        }

        // Prepopulate waiter
        if (order.waiter) {
            setSelectedWaiter(order.waiter);
        }

        // Prepopulate items from order as already sent/held
        const existingCartItems = (order.items || []).map(it => ({
            id: it.menu_item_id || it.id,
            name: it.menu_item ? it.menu_item.name : 'Item',
            code: it.menu_item?.code || '',
            price: parseFloat(it.unit_price),
            quantity: parseFloat(it.quantity),
            stationType: it.preparation_department || 'kitchen',
            stationName: it.preparation_department === 'bar' ? 'Central Bar' : 'Main Kitchen',
            notes: it.special_instructions || '',
            isExistingItem: true
        }));

        setCart(existingCartItems);
        setSelectedCartItemId(null);
        setShowRetrieveModal(false);
        setActiveTopTab('CATEGORY');
        addToast('Order Loaded', `Order #${order.order_number} loaded. You can now add new products and hold again!`, 'info');
    }, [addToast]);

    // CANCEL EDITING HELD ORDER
    const handleCancelEditOrder = useCallback(() => {
        setEditingOrder(null);
        setCart([]);
        setSelectedTable(null);
        setSelectedCartItemId(null);
        addToast('Edit Cancelled', 'Docket cleared and returned to fresh state', 'info');
    }, [addToast]);

    // SUBMIT ORDER OR ADD PRODUCTS TO HELD ORDER (IRP 200D Thermal Printer)
    const handleSubmitOrder = async () => {
        if (cart.length === 0) {
            addToast('Empty Order', 'Please add items before dispatching order.', 'warning');
            return;
        }

        // MANDATORY 1: Dining Table
        if (!selectedTable) {
            addToast('Table Mandatory', 'Please select a dining table before dispatching.', 'warning');
            playPosSound('void');
            setActiveTopTab('RMS');
            setRmsSubTab('TABLE');
            return;
        }

        // MANDATORY 2: Waiter Assignment
        if (!selectedWaiter) {
            addToast('Waiter Mandatory', 'Please select an assigned waiter before dispatching.', 'warning');
            playPosSound('void');
            setActiveTopTab('RMS');
            setRmsSubTab('WAITERS');
            return;
        }

        setIsSubmitting(true);

        // CASE A: Adding new products to an existing held order!
        if (editingOrder) {
            const newItems = cart.filter(it => !it.isExistingItem);
            if (newItems.length === 0) {
                addToast('No New Items', 'Please select additional items to add to this held order.', 'warning');
                setIsSubmitting(false);
                return;
            }

            const payload = {
                items: newItems.map(item => ({
                    menu_item_id: item.id,
                    quantity: item.quantity,
                    notes: item.notes || null,
                    modifiers: []
                }))
            };

            try {
                const addUrl = `${routes.addItemsBase || '/pos/orders'}/${editingOrder.id}/add-items`;
                const response = await fetch(addUrl, {
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
                    addToast('Submission Error', data.message || 'Error updating order.', 'danger');
                    setIsSubmitting(false);
                    return;
                }

                const updatedOrder = data.order;
                playPosSound('success');

                // Print supplementary tickets on IRP 200D for newly added items
                if (data.new_tickets && data.new_tickets.length > 0) {
                    data.new_tickets.forEach(ticket => {
                        const ticketHtml = buildIrp200dTicketHtml(updatedOrder, ticket, appName);
                        printIrp200d(ticketHtml);
                    });
                }

                // Update recent orders list
                setRecentOrders(prev => [updatedOrder, ...prev.filter(o => o.id !== updatedOrder.id)]);

                // Reset editing state and clear cart
                setEditingOrder(null);
                setCart([]);
                setSelectedCartItemId(null);
                setSelectedTable(null);

                addToast('Order Updated & Held', `New items dispatched to IRP 200D. Order #${updatedOrder.order_number} held on Table T-${updatedOrder.table?.table_number}!`, 'success');
                setActiveTopTab('CATEGORY');

            } catch (err) {
                console.error('Adding items failed:', err);
                addToast('Server Error', 'Failed to communicate with server.', 'danger');
            } finally {
                setIsSubmitting(false);
            }
            return;
        }

        // CASE B: Creating a fresh order and holding on table
        const payload = {
            order_type: orderType,
            restaurant_table_id: selectedTable.id,
            waiter_id: selectedWaiter.id,
            customer_name: roomCharge ? `Room: ${roomCharge}` : null,
            customer_phone: null,
            delivery_address: null,
            notes: coverCount > 1 ? `Covers: ${coverCount}` : null,
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
                addToast('Submission Error', data.message || 'Error creating order.', 'danger');
                setIsSubmitting(false);
                return;
            }

            const newOrder = data.order;
            playPosSound('success');

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
            addToast('Order Held', `Order #${newOrder.order_number} held on Table T-${selectedTable.table_number}`, 'success');

            // Reset current cart and clear table
            setCart([]);
            setSelectedCartItemId(null);
            allTablesList.forEach(t => {
                if (t.id === selectedTable.id) {
                    t.status = 'occupied';
                    t.active_order = newOrder;
                }
            });
            setSelectedTable(null);

            setActiveTopTab('CATEGORY');

        } catch (err) {
            console.error('Order submission failed:', err);
            addToast('Server Error', 'Failed to communicate with server.', 'danger');
        } finally {
            setIsSubmitting(false);
        }
    };

    // SETTLE / PAY ORDER IN ETHIOPIAN BIRR (ETB)
    const handleConfirmSettleOrder = async () => {
        if (!orderToSettle) return;
        setIsSubmitting(true);

        const totalDue = parseFloat(orderToSettle.total_amount || 0);
        const tenderedVal = settleAmountTendered ? parseFloat(settleAmountTendered) : totalDue;

        try {
            const settleUrl = `${routes.settleBase || '/pos/orders'}/${orderToSettle.id}/settle`;
            const response = await fetch(settleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    payment_method: settlePaymentMethod,
                    amount_tendered: tenderedVal
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                addToast('Settlement Error', data.message || 'Error settling order.', 'danger');
                setIsSubmitting(false);
                return;
            }

            const settled = data.order;
            playPosSound('success');

            // Optionally print settlement receipt to IRP 200D
            if (printReceiptOnSettle) {
                const receiptHtml = buildIrp200dSettlementReceiptHtml(settled, settlePaymentMethod, tenderedVal, appName);
                printIrp200d(receiptHtml);
            }

            // Update recent orders list
            setRecentOrders(prev => prev.map(o => o.id === settled.id ? settled : o));

            // Free the table
            if (settled.restaurant_table_id) {
                allTablesList.forEach(t => {
                    if (t.id === settled.restaurant_table_id) {
                        t.status = 'available';
                        t.active_order = null;
                    }
                });
            }

            setShowSettleModal(false);
            setOrderToSettle(null);
            setShowRetrieveModal(false);
            setSettleAmountTendered('');

            addToast('Settled & Paid', `Order #${settled.order_number} marked as Paid. Table freed!`, 'success');

        } catch (err) {
            console.error('Settlement error:', err);
            addToast('Server Error', 'Failed to settle order on server.', 'danger');
        } finally {
            setIsSubmitting(false);
        }
    };

    // Keyboard Shortcuts (F6 = Send/Hold Order, F7 = Void Selected Item)
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'F6') {
                e.preventDefault();
                handleSubmitOrder();
            } else if (e.key === 'F7') {
                e.preventDefault();
                voidSelectedItem();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [handleSubmitOrder, voidSelectedItem]);

    // Filtered orders for REFERENCE view
    const filteredOrders = useMemo(() => {
        if (referenceFilter === 'mine') {
            return recentOrders.filter(o => o.waiter_id === (selectedWaiter?.id || user.id));
        }
        return recentOrders;
    }, [recentOrders, referenceFilter, selectedWaiter, user.id]);

    // Format time and date matching reference layout
    const clockTimeStr = currentTime.toLocaleTimeString('en-US', { hour12: false });
    const clockDateStr = currentTime.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });

    return (
        <div className={`pos-container pos-theme-${theme} d-flex flex-column vh-100 w-100 overflow-hidden position-relative`}>
            {/* Smart Animated Ambient Background Effect */}
            <div className="smart-animated-bg">
                <div className="smart-blob smart-blob-1"></div>
                <div className="smart-blob smart-blob-2"></div>
                <div className="smart-blob smart-blob-3"></div>
            </div>

            <style>{`
                .pos-container {
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
                    letter-spacing: -0.011em;
                    position: relative;
                }
                .pos-theme-light {
                    background-color: #f1f5f9;
                    color: #0f172a;
                }
                .pos-theme-dark {
                    background-color: #0b1120;
                    color: #f1f5f9;
                }

                /* Smart Animated Ambient Background */
                .smart-animated-bg {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 0;
                    overflow: hidden;
                }
                .pos-theme-light .smart-animated-bg {
                    opacity: 0.70;
                }
                .pos-theme-dark .smart-animated-bg {
                    opacity: 0.40;
                }
                .smart-blob {
                    position: absolute;
                    border-radius: 50%;
                    filter: blur(80px);
                    will-change: transform;
                }
                .smart-blob-1 {
                    top: -10%;
                    right: 5%;
                    width: 460px;
                    height: 460px;
                    background: radial-gradient(circle, rgba(217, 119, 6, 0.22) 0%, rgba(217, 119, 6, 0) 70%);
                    animation: smartFloat1 18s ease-in-out infinite alternate;
                }
                .smart-blob-2 {
                    bottom: -15%;
                    left: 10%;
                    width: 560px;
                    height: 560px;
                    background: radial-gradient(circle, rgba(37, 99, 235, 0.20) 0%, rgba(37, 99, 235, 0) 70%);
                    animation: smartFloat2 22s ease-in-out infinite alternate;
                }
                .smart-blob-3 {
                    top: 40%;
                    right: 30%;
                    width: 420px;
                    height: 420px;
                    background: radial-gradient(circle, rgba(16, 185, 129, 0.18) 0%, rgba(16, 185, 129, 0) 70%);
                    animation: smartFloat3 26s ease-in-out infinite alternate;
                }
                @keyframes smartFloat1 {
                    0% { transform: translate(0, 0) scale(1); }
                    50% { transform: translate(-45px, 65px) scale(1.08); }
                    100% { transform: translate(35px, -35px) scale(0.95); }
                }
                @keyframes smartFloat2 {
                    0% { transform: translate(0, 0) scale(1); }
                    50% { transform: translate(65px, -55px) scale(1.06); }
                    100% { transform: translate(-35px, 45px) scale(0.92); }
                }
                @keyframes smartFloat3 {
                    0% { transform: translate(0, 0) scale(1); }
                    50% { transform: translate(-55px, -45px) scale(1.12); }
                    100% { transform: translate(45px, 35px) scale(0.96); }
                }

                /* Header Bar */
                .pos-theme-light .pos-header-bar {
                    background-color: #ffffff;
                    border-bottom: 1px solid #e2e8f0;
                }
                .pos-theme-dark .pos-header-bar {
                    background-color: #111c30;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                }

                .pos-header-box {
                    min-height: 52px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    text-align: center;
                    padding: 4px 12px;
                    font-weight: 600;
                    position: relative;
                    z-index: 1;
                }
                .pos-theme-light .pos-header-box {
                    background-color: #ffffff;
                    border-color: #e2e8f0 !important;
                    color: #0f172a;
                }
                .pos-theme-dark .pos-header-box {
                    background-color: #111c30;
                    border-color: rgba(255, 255, 255, 0.1) !important;
                    color: #f1f5f9;
                }

                /* Nav Tab Buttons */
                .pos-nav-tab-btn {
                    font-weight: 800;
                    letter-spacing: 0.5px;
                    transition: all 0.15s ease;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100%;
                    padding: 0 14px;
                    position: relative;
                    z-index: 1;
                }
                .pos-theme-light .pos-nav-tab-btn {
                    background-color: #f8fafc;
                    border: 1px solid #cbd5e1;
                    color: #334155;
                }
                .pos-theme-light .pos-nav-tab-btn:hover {
                    background-color: #e2e8f0;
                    color: #0f172a;
                }
                .pos-theme-light .pos-nav-tab-btn.active {
                    background-color: #0284c7 !important;
                    color: #ffffff !important;
                    border-color: #0284c7 !important;
                    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
                }
                .pos-theme-dark .pos-nav-tab-btn {
                    background-color: #16243d;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: #94a3b8;
                }
                .pos-theme-dark .pos-nav-tab-btn:hover {
                    background-color: #1e3a5f;
                    color: #ffffff;
                }
                .pos-theme-dark .pos-nav-tab-btn.active {
                    background-color: #0284c7 !important;
                    color: #ffffff !important;
                    border-color: #0284c7 !important;
                    box-shadow: 0 2px 8px rgba(2, 132, 199, 0.5);
                }

                /* Subnav buttons */
                .pos-subnav-btn {
                    font-weight: 700;
                    padding: 8px 18px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                }
                .pos-theme-light .pos-subnav-btn {
                    background-color: #ffffff;
                    border: 1px solid #cbd5e1;
                    color: #334155;
                }
                .pos-theme-light .pos-subnav-btn:hover {
                    background-color: #e2e8f0;
                    color: #0f172a;
                }
                .pos-theme-light .pos-subnav-btn.active {
                    background-color: #0284c7 !important;
                    color: #ffffff !important;
                    border-color: #0284c7 !important;
                }
                .pos-theme-dark .pos-subnav-btn {
                    background-color: #16243d;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: #cbd5e1;
                }
                .pos-theme-dark .pos-subnav-btn:hover {
                    background-color: #1e3a5f;
                    color: #ffffff;
                }
                .pos-theme-dark .pos-subnav-btn.active {
                    background-color: #0284c7 !important;
                    color: #ffffff !important;
                    border-color: #0284c7 !important;
                }

                /* Docket Pane */
                .pos-docket-pane {
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                    position: relative;
                    z-index: 1;
                }
                .pos-theme-light .pos-docket-pane {
                    background-color: #ffffff;
                    border-right: 2px solid #e2e8f0;
                }
                .pos-theme-dark .pos-docket-pane {
                    background-color: #111c30;
                    border-right: 2px solid rgba(255, 255, 255, 0.08);
                }

                /* Docket Table */
                .pos-docket-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .pos-theme-light .pos-docket-table {
                    color: #0f172a;
                }
                .pos-theme-light .pos-docket-table th {
                    background-color: #f8fafc;
                    color: #475569;
                    padding: 9px 10px;
                    font-size: 13px;
                    font-weight: 800;
                    text-transform: uppercase;
                    border-bottom: 2px solid #cbd5e1;
                }
                .pos-theme-light .pos-docket-table td {
                    padding: 9px 10px;
                    font-size: 13.5px;
                    border-bottom: 1px solid #f1f5f9;
                    cursor: pointer;
                }
                .pos-theme-light .pos-docket-row:hover {
                    background-color: #f8fafc;
                }
                .pos-theme-light .pos-docket-row.selected {
                    background-color: #e0f2fe !important;
                    color: #0369a1 !important;
                    border-left: 4px solid #0284c7;
                }

                .pos-theme-dark .pos-docket-table {
                    color: #f1f5f9;
                }
                .pos-theme-dark .pos-docket-table th {
                    background-color: #16243d;
                    color: #94a3b8;
                    padding: 9px 10px;
                    font-size: 13px;
                    font-weight: 800;
                    text-transform: uppercase;
                    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
                }
                .pos-theme-dark .pos-docket-table td {
                    padding: 9px 10px;
                    font-size: 13.5px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
                    cursor: pointer;
                }
                .pos-theme-dark .pos-docket-row:hover {
                    background-color: #16243d;
                }
                .pos-theme-dark .pos-docket-row.selected {
                    background-color: #1e3a5f !important;
                    color: #38bdf8 !important;
                    border-left: 4px solid #38bdf8;
                }

                /* Category Tiles */
                .pos-category-tile {
                    border-radius: 8px;
                    min-height: 120px;
                    cursor: pointer;
                    transition: all 0.18s ease;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }
                .pos-theme-light .pos-category-tile {
                    background-color: #ffffff;
                    border: 1px solid #cbd5e1;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .pos-theme-light .pos-category-tile:hover {
                    border-color: #0284c7;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(2,132,199,0.15);
                }
                .pos-theme-light .pos-category-tile-header {
                    background-color: #f8fafc;
                    font-size: 11.5px;
                    color: #0284c7;
                    font-weight: 700;
                    padding: 5px 8px;
                    text-align: right;
                    border-bottom: 1px solid #e2e8f0;
                }
                .pos-theme-light .pos-category-tile-body {
                    flex-grow: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 12px;
                    text-align: center;
                    font-size: 17px;
                    font-weight: 800;
                    color: #0f172a;
                }

                .pos-theme-dark .pos-category-tile {
                    background-color: #111c30;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
                }
                .pos-theme-dark .pos-category-tile:hover {
                    border-color: #38bdf8;
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(56,189,248,0.2);
                }
                .pos-theme-dark .pos-category-tile-header {
                    background-color: #16243d;
                    font-size: 11.5px;
                    color: #38bdf8;
                    font-weight: 700;
                    padding: 5px 8px;
                    text-align: right;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                }
                .pos-theme-dark .pos-category-tile-body {
                    flex-grow: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 12px;
                    text-align: center;
                    font-size: 17px;
                    font-weight: 800;
                    color: #f8fafc;
                }

                /* Item Tiles */
                .pos-item-tile {
                    border-radius: 8px;
                    padding: 12px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    min-height: 100px;
                }
                .pos-theme-light .pos-item-tile {
                    background-color: #ffffff;
                    border: 1px solid #cbd5e1;
                    color: #0f172a;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
                }
                .pos-theme-light .pos-item-tile:hover {
                    border-color: #0284c7;
                    background-color: #f0f9ff;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(2,132,199,0.12);
                }
                .pos-theme-dark .pos-item-tile {
                    background-color: #111c30;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: #f8fafc;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
                }
                .pos-theme-dark .pos-item-tile:hover {
                    border-color: #38bdf8;
                    background-color: #16243d;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 14px rgba(56,189,248,0.18);
                }

                /* Buttons */
                .pos-btn-void {
                    background-color: #dc2626;
                    color: #ffffff;
                    border: 1px solid #b91c1c;
                    font-weight: 800;
                    letter-spacing: 0.5px;
                    cursor: pointer;
                    transition: all 0.15s;
                }
                .pos-btn-void:hover {
                    background-color: #b91c1c;
                }
                .pos-btn-order {
                    background-color: #16a34a;
                    color: #ffffff;
                    border: 1px solid #15803d;
                    font-weight: 800;
                    letter-spacing: 0.5px;
                    cursor: pointer;
                    transition: all 0.15s;
                }
                .pos-btn-order:hover {
                    background-color: #15803d;
                }
                .pos-btn-total {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    font-weight: 800;
                }
                .pos-theme-light .pos-btn-total {
                    background-color: #0f172a;
                    border: 1px solid #1e293b;
                    color: #ffffff;
                }
                .pos-theme-dark .pos-btn-total {
                    background-color: #020617;
                    border: 1px solid rgba(255, 255, 255, 0.15);
                    color: #ffffff;
                }

                /* Reference Table */
                .pos-ref-table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 13px;
                }
                .pos-theme-light .pos-ref-table {
                    color: #0f172a;
                }
                .pos-theme-light .pos-ref-table th {
                    background-color: #f1f5f9;
                    color: #334155;
                    padding: 9px 10px;
                    border: 1px solid #cbd5e1;
                    text-align: left;
                    font-weight: 800;
                }
                .pos-theme-light .pos-ref-table td {
                    padding: 9px 10px;
                    border: 1px solid #e2e8f0;
                    background-color: #ffffff;
                }
                .pos-theme-light .pos-ref-row:hover td {
                    background-color: #f0f9ff;
                }
                .pos-theme-light .pos-ref-row.selected td {
                    background-color: #e0f2fe;
                    color: #0369a1;
                    font-weight: 600;
                }

                .pos-theme-dark .pos-ref-table {
                    color: #f8fafc;
                }
                .pos-theme-dark .pos-ref-table th {
                    background-color: #16243d;
                    color: #94a3b8;
                    padding: 9px 10px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    text-align: left;
                    font-weight: 800;
                }
                .pos-theme-dark .pos-ref-table td {
                    padding: 9px 10px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    background-color: #111c30;
                    color: #f1f5f9;
                }
                .pos-theme-dark .pos-ref-row:hover td {
                    background-color: #1a2a46;
                }
                .pos-theme-dark .pos-ref-row.selected td {
                    background-color: #1e3a5f;
                    color: #38bdf8;
                    font-weight: 600;
                }

                /* Bottom Bar Buttons */
                .pos-bottom-bar-btn {
                    font-weight: 700;
                    padding: 9px 16px;
                    cursor: pointer;
                    font-size: 13px;
                    letter-spacing: 0.5px;
                    transition: all 0.15s;
                }
                .pos-theme-light .pos-bottom-bar-btn {
                    background-color: #ffffff;
                    border: 1px solid #cbd5e1;
                    color: #1e293b;
                }
                .pos-theme-light .pos-bottom-bar-btn:hover {
                    background-color: #f1f5f9;
                    border-color: #94a3b8;
                    color: #0f172a;
                }
                .pos-theme-dark .pos-bottom-bar-btn {
                    background-color: #16243d;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: #f1f5f9;
                }
                .pos-theme-dark .pos-bottom-bar-btn:hover {
                    background-color: #1e3a5f;
                    border-color: #38bdf8;
                    color: #ffffff;
                }
            `}</style>

            {/* 1. TOP HEADER (Exact 1:1 Layout from Reference Screenshots in Light Theme) */}
            <header className="row g-0 border-bottom shrink-0 shadow-2xs pos-header-bar" style={{ minHeight: '54px' }}>
                {/* Box 1: App / Branch Name */}
                <div className="col-4 col-sm-3 col-md-2 pos-header-box border-end">
                    <div className="lh-sm text-truncate w-100 fw-bold" style={{ fontSize: '13px' }}>{appName}</div>
                    <div className="text-primary text-truncate w-100 small fw-bold">({branch.name})</div>
                </div>

                {/* Box 2: Staff / Waiter Name (from HR Waiter Department) */}
                <div className="col-4 col-sm-3 col-md-2 pos-header-box border-end">
                    <div className="small text-muted text-uppercase" style={{ fontSize: '10px' }}>Staff / Waiter</div>
                    <div className="text-truncate w-100 fw-bold">
                        {selectedWaiter ? selectedWaiter.name : (user.name || 'Admin')}
                    </div>
                </div>

                {/* Box 3: Real-Time Clock & Date */}
                <div className="col-4 col-sm-2 col-md-2 pos-header-box border-end font-mono">
                    <div className="fw-bolder fs-6 text-primary lh-1 mb-0.5">{clockTimeStr}</div>
                    <div className="text-muted small" style={{ fontSize: '10px' }}>{clockDateStr}</div>
                </div>

                {/* Box 4: Navigation Tabs (CATEGORY | RMS | REFERENCE | FAVORITE | SEARCH | THEME | EXIT) */}
                <div className="col-12 col-sm-4 col-md-6 d-flex align-items-stretch overflow-x-auto text-nowrap flex-nowrap">
                    <button
                        type="button"
                        onClick={() => { setActiveTopTab('CATEGORY'); setSelectedCategory(null); }}
                        className={`flex-fill pos-nav-tab-btn ${activeTopTab === 'CATEGORY' ? 'active' : ''}`}
                    >
                        CATEGORY
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTopTab('RMS')}
                        className={`flex-fill pos-nav-tab-btn ${activeTopTab === 'RMS' ? 'active' : ''}`}
                    >
                        RMS
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTopTab('REFERENCE')}
                        className={`flex-fill pos-nav-tab-btn ${activeTopTab === 'REFERENCE' ? 'active' : ''}`}
                    >
                        REFERENCE
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTopTab('FAVORITE')}
                        className={`flex-fill pos-nav-tab-btn ${activeTopTab === 'FAVORITE' ? 'active' : ''}`}
                    >
                        FAVORITE
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTopTab('SEARCH')}
                        className={`flex-fill pos-nav-tab-btn ${activeTopTab === 'SEARCH' ? 'active' : ''}`}
                    >
                        SEARCH
                    </button>

                    {/* Dark Mode / Light Mode Toggle */}
                    <button
                        type="button"
                        onClick={toggleTheme}
                        className="flex-fill pos-nav-tab-btn"
                        title={theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
                        style={{ minWidth: '48px' }}
                    >
                        <i className={`bi ${theme === 'dark' ? 'bi-sun-fill text-warning' : 'bi-moon-stars-fill text-primary'}`}></i>
                        <span className="d-none d-xl-inline ms-1 small fw-bold">
                            {theme === 'dark' ? 'LIGHT' : 'DARK'}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setShowExitModal(true)}
                        className="flex-fill pos-nav-tab-btn text-danger fw-bold"
                    >
                        EXIT
                    </button>
                </div>
            </header>

            {/* Secondary Toolbar under Active Top Tab */}
            {activeTopTab === 'RMS' && (
                <div className={`d-flex align-items-center gap-1 p-2 border-bottom shrink-0 overflow-x-auto shadow-2xs ${theme === 'dark' ? 'bg-dark' : 'bg-white'}`}>
                    <button
                        type="button"
                        onClick={() => setRmsSubTab('TABLE')}
                        className={`pos-subnav-btn rounded-1 ${rmsSubTab === 'TABLE' ? 'active' : ''}`}
                    >
                        <i className="bi bi-grid-3x3 me-1.5"></i>
                        TABLE {selectedTable ? `(T-${selectedTable.table_number})` : '*'}
                    </button>

                    <button
                        type="button"
                        onClick={() => setRmsSubTab('WAITERS')}
                        className={`pos-subnav-btn rounded-1 ${rmsSubTab === 'WAITERS' ? 'active' : ''}`}
                    >
                        <i className="bi bi-person-badge me-1.5"></i>
                        WAITERS {selectedWaiter ? `(${selectedWaiter.name.split(' ')[0]})` : '*'}
                    </button>

                    <button
                        type="button"
                        onClick={() => setRmsSubTab('COVER')}
                        className={`pos-subnav-btn rounded-1 ${rmsSubTab === 'COVER' ? 'active' : ''}`}
                    >
                        <i className="bi bi-people me-1.5"></i>
                        COVER ({coverCount})
                    </button>

                    <button
                        type="button"
                        onClick={() => setRmsSubTab('ROOM CHARGE')}
                        className={`pos-subnav-btn rounded-1 ${rmsSubTab === 'ROOM CHARGE' ? 'active' : ''}`}
                    >
                        <i className="bi bi-door-closed me-1.5"></i>
                        ROOM CHARGE {roomCharge ? `(${roomCharge})` : ''}
                    </button>
                </div>
            )}

            {activeTopTab === 'REFERENCE' && (
                <div className="d-flex align-items-center gap-1 p-2 bg-white border-bottom shrink-0 overflow-x-auto shadow-2xs">
                    <button
                        type="button"
                        onClick={() => setReferenceSubTab('Orders')}
                        className={`pos-subnav-btn rounded-1 ${referenceSubTab === 'Orders' ? 'active' : ''}`}
                    >
                        <i className="bi bi-receipt me-1.5"></i>
                        Orders
                    </button>

                    <button
                        type="button"
                        onClick={() => setReferenceSubTab('BILL SETTLEMENT')}
                        className={`pos-subnav-btn rounded-1 ${referenceSubTab === 'BILL SETTLEMENT' ? 'active' : ''}`}
                    >
                        <i className="bi bi-cash-stack me-1.5"></i>
                        BILL SETTLEMENT (PAID)
                    </button>
                </div>
            )}

            {/* Mobile View Toggle Bar (Only visible on small viewports) */}
            <div className={`d-flex d-md-none border-bottom p-1.5 gap-1 shrink-0 ${theme === 'dark' ? 'bg-dark' : 'bg-white'}`}>
                <button
                    type="button"
                    onClick={() => setMobileView('content')}
                    className={`btn btn-sm flex-fill fw-bold ${mobileView === 'content' ? 'btn-primary' : 'btn-outline-secondary'}`}
                >
                    <i className="bi bi-grid-fill me-1"></i> Screen
                </button>
                <button
                    type="button"
                    onClick={() => setMobileView('docket')}
                    className={`btn btn-sm flex-fill fw-bold ${mobileView === 'docket' ? 'btn-primary' : 'btn-outline-secondary'}`}
                >
                    <i className="bi bi-receipt me-1"></i> Docket ({cartTotals.itemCount}) - ETB {cartTotals.grandTotal.toFixed(2)}
                </button>
            </div>

            {/* 2. MAIN SPLIT SCREEN (Left: Docket / Right: Main Content) */}
            <div className="flex-grow-1 d-flex overflow-hidden">
                {/* LEFT COLUMN: THE ORDER DOCKET */}
                <div 
                    className={`pos-docket-pane ${mobileView === 'content' ? 'd-none d-md-flex' : 'd-flex w-100'}`} 
                    style={{ width: '390px', minWidth: '320px', maxWidth: '420px' }}
                >
                    {/* Editing Held Order Warning Banner */}
                    {editingOrder && (
                        <div className="p-2 bg-warning bg-opacity-25 border-bottom border-warning d-flex align-items-center justify-content-between">
                            <div>
                                <strong className="text-dark small d-block">ADDING ITEMS TO HELD ORDER</strong>
                                <span className="text-muted font-mono" style={{ fontSize: '11px' }}>
                                    #{editingOrder.order_number} &bull; T-{editingOrder.table?.table_number}
                                </span>
                            </div>
                            <button
                                type="button"
                                onClick={handleCancelEditOrder}
                                className="btn btn-xs btn-outline-danger py-0 px-2 fw-bold"
                            >
                                Cancel
                            </button>
                        </div>
                    )}

                    {/* Mandatory Table & Waiter Status Pill */}
                    <div className={`p-2 border-bottom d-flex align-items-center justify-content-between gap-1.5 ${theme === 'dark' ? 'bg-dark' : 'bg-light'}`}>
                        <button
                            type="button"
                            onClick={() => { setActiveTopTab('RMS'); setRmsSubTab('TABLE'); }}
                            className={`btn btn-xs py-1 px-2.5 text-start rounded-2 flex-fill ${
                                selectedTable ? 'btn-outline-primary fw-bold bg-white' : 'btn-danger text-white fw-bold animate-pulse'
                            }`}
                            title="Click to select table (Mandatory)"
                        >
                            <i className="bi bi-grid-3x3 me-1"></i>
                            {selectedTable ? `T-${selectedTable.table_number}` : 'Table * (Select)'}
                        </button>

                        <button
                            type="button"
                            onClick={() => { setActiveTopTab('RMS'); setRmsSubTab('WAITERS'); }}
                            className={`btn btn-xs py-1 px-2.5 text-start rounded-2 flex-fill ${
                                selectedWaiter ? 'btn-outline-dark fw-bold bg-white' : 'btn-danger text-white fw-bold animate-pulse'
                            }`}
                            title="Click to assign waiter (Mandatory)"
                        >
                            <i className="bi bi-person-fill text-primary me-1"></i>
                            {selectedWaiter ? selectedWaiter.name.split(' ')[0] : 'Waiter * (Select)'}
                        </button>
                    </div>

                    {/* Docket Table Header & Items List */}
                    <div className="flex-grow-1 overflow-y-auto">
                        <table className="pos-docket-table">
                            <thead>
                                <tr>
                                    <th style={{ width: '45%' }}>Item</th>
                                    <th className="text-center" style={{ width: '15%' }}>Qty</th>
                                    <th className="text-end" style={{ width: '20%' }}>UPrice</th>
                                    <th className="text-end" style={{ width: '20%' }}>TPrice</th>
                                </tr>
                            </thead>
                            <tbody>
                                {cart.length === 0 ? (
                                    <tr>
                                        <td colSpan="4" className="text-center py-5 text-muted">
                                            <i className="bi bi-cart3 fs-1 d-block mb-2 opacity-30"></i>
                                            <div className="fw-semibold">Docket is empty</div>
                                            <small className="text-secondary">Select items from Category to build order</small>
                                        </td>
                                    </tr>
                                ) : (
                                    cart.map(item => {
                                        const isSelected = selectedCartItemId === item.id;
                                        return (
                                            <tr 
                                                key={item.id} 
                                                className={`pos-docket-row ${isSelected ? 'selected' : ''}`}
                                                onClick={() => setSelectedCartItemId(item.id)}
                                            >
                                                <td>
                                                    <div className="d-flex align-items-center gap-1">
                                                        {item.isExistingItem ? (
                                                            <span className="badge bg-secondary bg-opacity-25 text-dark font-mono" style={{ fontSize: '9px' }}>
                                                                HELD
                                                            </span>
                                                        ) : (
                                                            <span className="badge bg-success bg-opacity-25 text-success font-mono" style={{ fontSize: '9px' }}>
                                                                NEW
                                                            </span>
                                                        )}
                                                        <div className="fw-bold text-truncate" style={{ maxWidth: '130px' }}>
                                                            {item.name}
                                                        </div>
                                                    </div>
                                                    {item.notes && (
                                                        <div className="text-danger small fst-italic" style={{ fontSize: '11px' }}>
                                                            * {item.notes}
                                                        </div>
                                                    )}
                                                    {isSelected && !item.isExistingItem && (
                                                        <div className="mt-1 d-flex gap-1">
                                                            <button 
                                                                type="button"
                                                                onClick={(e) => { e.stopPropagation(); updateQuantity(item.id, -1); }}
                                                                className="btn btn-xs btn-outline-secondary py-0 px-1.5"
                                                            >
                                                                -
                                                            </button>
                                                            <button 
                                                                type="button"
                                                                onClick={(e) => { e.stopPropagation(); updateQuantity(item.id, 1); }}
                                                                className="btn btn-xs btn-outline-secondary py-0 px-1.5"
                                                            >
                                                                +
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={(e) => { e.stopPropagation(); setItemNotesModal(item); }}
                                                                className="btn btn-xs btn-outline-warning py-0 px-1.5"
                                                                title="Add kitchen note"
                                                            >
                                                                <i className="bi bi-pencil-fill"></i>
                                                            </button>
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="text-center font-mono fw-bold">{item.quantity}</td>
                                                <td className="text-end font-mono">{item.price.toFixed(2)}</td>
                                                <td className="text-end font-mono fw-bold">{(item.price * item.quantity).toFixed(2)}</td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Docket Bottom Action Bar: VOID (F7) | ORDER / HOLD (F6) | TOTAL (ETB) - Permission Guarded */}
                    <div className="row g-0 border-top shrink-0" style={{ height: '66px' }}>
                        {/* VOID (F7) */}
                        {can('pos.orders.cancel') && (
                            <div className={can('pos.orders.create') ? "col-4 d-flex" : "col-6 d-flex"}>
                                <button
                                    type="button"
                                    onClick={voidSelectedItem}
                                    className="pos-btn-void w-100 h-100 d-flex flex-column align-items-center justify-content-center p-1"
                                    title="Void selected line item (F7)"
                                >
                                    <span className="fs-6 fw-bold">VOID</span>
                                    <small className="opacity-90 font-mono" style={{ fontSize: '10px' }}>(F7)</small>
                                </button>
                            </div>
                        )}

                        {/* ORDER / HOLD (F6) */}
                        {can('pos.orders.create') && (
                            <div className={can('pos.orders.cancel') ? "col-4 d-flex" : "col-6 d-flex"}>
                                <button
                                    type="button"
                                    onClick={handleSubmitOrder}
                                    disabled={isSubmitting}
                                    className="pos-btn-order w-100 h-100 d-flex flex-column align-items-center justify-content-center p-1"
                                    title="Send / Hold order and print tickets to IRP 200D (F6)"
                                >
                                    <span className="fs-6 fw-bold">
                                        {isSubmitting ? 'HOLDING...' : (editingOrder ? 'ADD & HOLD' : 'ORDER / HOLD')}
                                    </span>
                                    <small className="opacity-90 font-mono" style={{ fontSize: '10px' }}>(F6)</small>
                                </button>
                            </div>
                        )}

                        {/* TOTAL (IN ETHIOPIAN BIRR) */}
                        <div className={(!can('pos.orders.cancel') && !can('pos.orders.create')) ? "col-12 pos-btn-total p-1" : ((!can('pos.orders.cancel') || !can('pos.orders.create')) ? "col-6 pos-btn-total p-1" : "col-4 pos-btn-total p-1")}>
                            <span className="small text-warning text-uppercase" style={{ fontSize: '10px', letterSpacing: '1px' }}>TOTAL (BIRR)</span>
                            <span className="fs-5 font-mono text-white fw-bolder">
                                ETB {cartTotals.grandTotal.toFixed(2)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* RIGHT COLUMN: MAIN CONTENT SCREEN (Light Theme) */}
                <div 
                    className={`flex-grow-1 d-flex flex-column overflow-hidden ${mobileView === 'docket' ? 'd-none d-md-flex' : 'd-flex'}`}
                    style={{ backgroundColor: theme === 'dark' ? 'transparent' : '#f8fafc' }}
                >
                    {/* A. CATEGORY VIEW (Photo 1 Style) */}
                    {activeTopTab === 'CATEGORY' && (
                        <div className="flex-grow-1 d-flex flex-column p-3 overflow-y-auto">
                            {!selectedCategory ? (
                                <>
                                    {/* Category Tiles Grid */}
                                    <div className="d-flex align-items-center justify-content-between mb-3">
                                        <div className="fs-5 fw-bold text-dark">Menu Categories</div>
                                        <div className="text-muted small fw-semibold">{categories.length} Categories Available</div>
                                    </div>

                                    <div className="row g-3">
                                        {categories.map(cat => {
                                            const itemCount = cat.menu_items ? cat.menu_items.length : 0;
                                            return (
                                                <div key={cat.id} className="col-6 col-sm-4 col-lg-3 col-xl-2">
                                                    <div 
                                                        className="pos-category-tile shadow-2xs h-100"
                                                        onClick={() => setSelectedCategory(cat)}
                                                    >
                                                        <div className="pos-category-tile-header font-mono">
                                                            Sub Categories:{itemCount}
                                                        </div>
                                                        <div className="pos-category-tile-body">
                                                            {cat.name}
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </>
                            ) : (
                                <>
                                    {/* Drilled-down Category Items View */}
                                    <div className="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                        <div className="d-flex align-items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setSelectedCategory(null)}
                                                className="btn btn-sm btn-outline-primary fw-bold"
                                            >
                                                <i className="bi bi-arrow-left me-1"></i>
                                                Categories
                                            </button>
                                            <div className="fs-5 fw-bold text-dark mb-0">
                                                {selectedCategory.name}
                                            </div>
                                        </div>

                                        <div className="text-muted font-mono small">
                                            {displayedItems.length} Items Available
                                        </div>
                                    </div>

                                    {/* Items Grid */}
                                    <div className="row g-2 overflow-y-auto">
                                        {displayedItems.length === 0 ? (
                                            <div className="col-12 text-center py-5 text-muted">
                                                No items in this category.
                                            </div>
                                        ) : (
                                            displayedItems.map(item => (
                                                <div key={item.id} className="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2">
                                                    <div 
                                                        className="pos-item-tile shadow-2xs h-100"
                                                        onClick={() => addToCart(item)}
                                                    >
                                                        <div>
                                                            <div className="fw-bold text-dark text-truncate" title={item.name}>
                                                                {item.name}
                                                            </div>
                                                            <div className="text-muted small font-mono" style={{ fontSize: '11px' }}>
                                                                {item.code || `ID-${item.id}`}
                                                            </div>
                                                        </div>
                                                        <div className="d-flex align-items-center justify-content-between mt-2">
                                                            <span className="badge bg-light text-secondary border small" style={{ fontSize: '9px' }}>
                                                                {item.preparation_department?.toUpperCase() || 'KITCHEN'}
                                                            </span>
                                                            <span className="font-mono text-primary fw-bold fs-6">
                                                                ETB {parseFloat(item.price).toFixed(2)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {/* B. RMS VIEW (Photo 2 Style) */}
                    {activeTopTab === 'RMS' && (
                        <div className="flex-grow-1 d-flex flex-column p-3 overflow-y-auto">
                            {/* RMS -> TABLE SUB-TAB */}
                            {rmsSubTab === 'TABLE' && (
                                <>
                                    <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        {/* Dining Area Switcher */}
                                        <div className="d-flex align-items-center gap-1 overflow-x-auto">
                                            <button
                                                type="button"
                                                onClick={() => setSelectedAreaId('all')}
                                                className={`btn btn-sm ${selectedAreaId === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            >
                                                All Areas ({allTablesList.length})
                                            </button>
                                            {diningAreas.map(area => (
                                                <button
                                                    key={area.id}
                                                    type="button"
                                                    onClick={() => setSelectedAreaId(area.id)}
                                                    className={`btn btn-sm ${selectedAreaId === area.id ? 'btn-primary' : 'btn-outline-secondary'}`}
                                                >
                                                    {area.name} ({area.tables ? area.tables.length : 0})
                                                </button>
                                            ))}
                                        </div>

                                        {/* Search Filter */}
                                        <div style={{ maxWidth: '200px' }}>
                                            <input
                                                type="text"
                                                value={tableSearchFilter}
                                                onChange={e => setTableSearchFilter(e.target.value)}
                                                placeholder="Search Table..."
                                                className="form-control form-control-sm border"
                                            />
                                        </div>
                                    </div>

                                    {/* Tables Grid with Instant Add Products & Settle */}
                                    <div className="row g-3">
                                        {filteredTablesList.map(table => {
                                            const isSelected = selectedTable && selectedTable.id === table.id;
                                            const isOccupied = table.status === 'occupied';
                                            const activeOrder = table.active_order;

                                            return (
                                                <div key={table.id} className="col-6 col-sm-4 col-md-3 col-lg-2">
                                                    <div 
                                                        className={`card h-100 p-2.5 cursor-pointer transition-all border ${
                                                            isSelected 
                                                                ? 'bg-primary text-white border-primary shadow-md' 
                                                                : isOccupied 
                                                                    ? 'bg-warning bg-opacity-10 border-warning text-dark' 
                                                                    : (theme === 'dark' ? 'bg-dark bg-opacity-75 border-secondary border-opacity-25 text-white hover-lift' : 'bg-white border-secondary border-opacity-25 text-dark hover-lift')
                                                        }`}
                                                        onClick={() => handleSelectTable(table)}
                                                    >
                                                        <div className="d-flex align-items-center justify-content-between mb-1.5">
                                                            <span className="badge bg-light text-secondary border font-mono">
                                                                {table.capacity || 4} Seats
                                                            </span>
                                                            <span className={`badge ${isSelected ? 'bg-white text-primary' : isOccupied ? 'bg-warning text-dark' : 'bg-success'}`}>
                                                                {isSelected ? 'SELECTED' : isOccupied ? 'OCCUPIED' : 'AVAILABLE'}
                                                            </span>
                                                        </div>

                                                        <div className="text-center py-2">
                                                            <div className="fs-3 fw-bold font-mono">T-{table.table_number}</div>
                                                            <small className={isSelected ? 'text-white-50' : 'text-muted'}>{table.area_name}</small>
                                                        </div>

                                                        {/* Quick Table Actions when Occupied */}
                                                        {isOccupied && activeOrder && (
                                                            <div className="mt-1 pt-1 border-top border-warning border-opacity-50 d-flex flex-column gap-1">
                                                                <div className="small font-mono text-truncate text-center" style={{ fontSize: '11px' }}>
                                                                    #{activeOrder.order_number.slice(-8)} &bull; <strong>ETB {parseFloat(activeOrder.total_amount || 0).toFixed(0)}</strong>
                                                                </div>
                                                                <div className="d-flex gap-1">
                                                                    {(can('pos.orders.edit') || can('pos.orders.create')) && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={(e) => { e.stopPropagation(); handleLoadOrderToEdit(activeOrder); }}
                                                                            className="btn btn-xs btn-outline-dark py-0 flex-fill fw-bold"
                                                                            title="Add new products to this held order"
                                                                        >
                                                                            + Add Items
                                                                        </button>
                                                                    )}
                                                                    {(can('finance.view') || can('pos.orders.edit')) && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={(e) => { e.stopPropagation(); setOrderToSettle(activeOrder); setShowSettleModal(true); }}
                                                                            className="btn btn-xs btn-success py-0 flex-fill fw-bold"
                                                                            title="Settle / Paid"
                                                                        >
                                                                            Settle
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </>
                            )}

                            {/* RMS -> WAITERS SUB-TAB (FROM HR WAITER DEPARTMENT) */}
                            {rmsSubTab === 'WAITERS' && (
                                <>
                                    <div className="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <div className="fs-5 fw-bold text-dark">Staff / Waiter Directory</div>
                                            <small className="text-muted">Waiters retrieved from HR Module (Dining & Floor Service Department)</small>
                                        </div>
                                        <div style={{ maxWidth: '240px' }}>
                                            <input
                                                type="text"
                                                value={waiterSearchQuery}
                                                onChange={e => setWaiterSearchQuery(e.target.value)}
                                                placeholder="Filter staff..."
                                                className="form-control form-control-sm border"
                                            />
                                        </div>
                                    </div>

                                    <div className="row g-3">
                                        {filteredWaiters.map(waiter => {
                                            const isSelected = selectedWaiter && selectedWaiter.id === waiter.id;
                                            return (
                                                <div key={waiter.id} className="col-6 col-sm-4 col-md-3 col-lg-2">
                                                    <div
                                                        className={`card h-100 p-3 text-center border cursor-pointer transition-all ${
                                                            isSelected ? 'bg-primary text-white border-primary shadow-md' : (theme === 'dark' ? 'bg-dark bg-opacity-75 border-secondary border-opacity-25 text-white hover-lift' : 'bg-white text-dark hover-lift')
                                                        }`}
                                                        onClick={() => handleSelectWaiter(waiter)}
                                                    >
                                                        <div className="mb-2">
                                                            <div className={`rounded-circle d-inline-flex p-3 ${isSelected ? 'bg-white bg-opacity-25 text-white' : 'bg-light text-primary'}`}>
                                                                <i className="bi bi-person-fill fs-3"></i>
                                                            </div>
                                                        </div>
                                                        <div className="fw-bold text-truncate">{waiter.name}</div>
                                                        <small className={isSelected ? 'text-white-50' : 'text-muted'}>{waiter.username || 'Staff'}</small>
                                                        {isSelected && (
                                                            <div className="mt-2 badge bg-white text-primary">ASSIGNED</div>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </>
                            )}

                            {/* RMS -> COVER SUB-TAB */}
                            {rmsSubTab === 'COVER' && (
                                <div className="text-center py-4">
                                    <div className="fs-5 fw-bold text-dark mb-3">Select Number of Guests (Covers)</div>
                                    <div className="d-flex flex-wrap justify-content-center gap-2" style={{ maxWidth: '600px', margin: '0 auto' }}>
                                        {[1, 2, 3, 4, 5, 6, 7, 8, 10, 12, 16].map(num => (
                                            <button
                                                key={num}
                                                type="button"
                                                onClick={() => {
                                                    setCoverCount(num);
                                                    addToast('Covers Updated', `Guests set to ${num}`, 'info');
                                                    setActiveTopTab('CATEGORY');
                                                }}
                                                className={`btn btn-lg font-mono fw-bold px-4 py-3 ${coverCount === num ? 'btn-primary' : 'btn-outline-secondary bg-white'}`}
                                            >
                                                {num}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* RMS -> ROOM CHARGE SUB-TAB */}
                            {rmsSubTab === 'ROOM CHARGE' && (
                                <div className="py-4" style={{ maxWidth: '480px', margin: '0 auto' }}>
                                    <div className="fs-5 fw-bold text-dark mb-3">Room or Folio Charge</div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted">Room Number / Member Folio</label>
                                        <input
                                            type="text"
                                            value={roomCharge}
                                            onChange={e => setRoomCharge(e.target.value)}
                                            placeholder="e.g. Room 402"
                                            className="form-control form-control-lg border"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            addToast('Room Set', `Charge directed to ${roomCharge}`, 'info');
                                            setActiveTopTab('CATEGORY');
                                        }}
                                        className="btn btn-primary w-100 fw-bold py-2"
                                    >
                                        Confirm Room Charge
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* C. REFERENCE VIEW (Photo 3 Style + SETTLE / PAID) */}
                    {activeTopTab === 'REFERENCE' && (
                        <div className="flex-grow-1 d-flex flex-column overflow-hidden">
                            {/* Orders Table Pane */}
                            <div className="flex-grow-1 overflow-auto p-2">
                                <table className="pos-ref-table">
                                    <thead>
                                        <tr>
                                            <th style={{ width: '4%' }}>SN</th>
                                            <th style={{ width: '15%' }}>VoucherID</th>
                                            <th style={{ width: '15%' }}>Date & Time</th>
                                            <th style={{ width: '12%' }}>Total (ETB)</th>
                                            <th style={{ width: '10%' }}>Status</th>
                                            <th style={{ width: '12%' }}>Device Name</th>
                                            <th style={{ width: '12%' }}>Waiter</th>
                                            <th style={{ width: '10%' }}>Table</th>
                                            <th style={{ width: '8%' }}>Cover</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredOrders.length === 0 ? (
                                            <tr>
                                                <td colSpan="9" className="text-center py-5 text-muted">
                                                    No orders found in reference log.
                                                </td>
                                            </tr>
                                        ) : (
                                            filteredOrders.map((ord, idx) => {
                                                const isSelected = selectedReferenceOrder && selectedReferenceOrder.id === ord.id;
                                                const orderTime = new Date(ord.created_at).toLocaleString();
                                                const waiterName = ord.waiter ? ord.waiter.name : (selectedWaiter ? selectedWaiter.name : 'Staff');
                                                const tableName = ord.table ? `T-${ord.table.table_number}` : 'Takeaway';
                                                const isPaid = ord.status === 'completed';

                                                return (
                                                    <tr
                                                        key={ord.id}
                                                        className={`pos-ref-row ${isSelected ? 'selected' : ''}`}
                                                        onClick={() => setSelectedReferenceOrder(ord)}
                                                    >
                                                        <td>{idx + 1}</td>
                                                        <td className="font-mono fw-bold text-primary">#{ord.order_number}</td>
                                                        <td className="font-mono small">{orderTime}</td>
                                                        <td className="font-mono fw-bold">ETB {parseFloat(ord.total_amount).toFixed(2)}</td>
                                                        <td>
                                                            <span className={`badge ${isPaid ? 'bg-success' : 'bg-warning text-dark'}`}>
                                                                {isPaid ? 'PAID / SETTLED' : 'HELD / OPEN'}
                                                            </span>
                                                        </td>
                                                        <td>IRP 200D</td>
                                                        <td>{waiterName}</td>
                                                        <td>{tableName}</td>
                                                        <td className="text-center">{ord.guest_count || 1}</td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Bottom Action Bar matching Photo 3 Exactly + SETTLE & ADD ITEMS - Permission Guarded */}
                            <div className="d-flex align-items-center gap-1.5 p-2 bg-white border-top shrink-0 overflow-x-auto shadow-2xs">
                                {/* Settle / Paid Button */}
                                {(can('finance.view') || can('pos.orders.edit')) && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (!selectedReferenceOrder) {
                                                addToast('Select Order', 'Click an order row to settle.', 'warning');
                                                return;
                                            }
                                            if (selectedReferenceOrder.status === 'completed') {
                                                addToast('Already Paid', 'This order is already marked as Paid / Settled.', 'info');
                                                return;
                                            }
                                            setOrderToSettle(selectedReferenceOrder);
                                            setShowSettleModal(true);
                                        }}
                                        className="pos-bottom-bar-btn rounded-1 btn-success text-white fw-bold"
                                        style={{ backgroundColor: '#16a34a', borderColor: '#15803d' }}
                                    >
                                        <i className="bi bi-cash-coin me-1"></i>
                                        SETTLE / PAID
                                    </button>
                                )}

                                {/* Add Items / Hold Again Button */}
                                {(can('pos.orders.edit') || can('pos.orders.create')) && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (!selectedReferenceOrder) {
                                                addToast('Select Order', 'Click an order to add items.', 'warning');
                                                return;
                                            }
                                            if (selectedReferenceOrder.status === 'completed') {
                                                addToast('Order Completed', 'Cannot add items to an already settled order.', 'warning');
                                                return;
                                            }
                                            handleLoadOrderToEdit(selectedReferenceOrder);
                                        }}
                                        className="pos-bottom-bar-btn rounded-1 btn-primary text-white fw-bold"
                                        style={{ backgroundColor: '#0284c7', borderColor: '#0284c7' }}
                                    >
                                        <i className="bi bi-plus-circle me-1"></i>
                                        ADD ITEMS / HOLD
                                    </button>
                                )}

                                <button
                                    type="button"
                                    onClick={() => {
                                        if (!selectedReferenceOrder) {
                                            addToast('Select Order', 'Click an order row to retrieve.', 'warning');
                                            return;
                                        }
                                        setShowRetrieveModal(true);
                                    }}
                                    className="pos-bottom-bar-btn rounded-1"
                                >
                                    RETRIEVE
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setReferenceFilter('mine')}
                                    className={`pos-bottom-bar-btn rounded-1 ${referenceFilter === 'mine' ? 'bg-primary text-white' : ''}`}
                                >
                                    SHOW MINE
                                </button>

                                <button
                                    type="button"
                                    onClick={() => setReferenceFilter('all')}
                                    className={`pos-bottom-bar-btn rounded-1 ${referenceFilter === 'all' ? 'bg-primary text-white' : ''}`}
                                >
                                    SHOW ALL
                                </button>

                                <button
                                    type="button"
                                    onClick={() => addToast('Notice', 'Split bill is available at cashier settlement.', 'info')}
                                    className="pos-bottom-bar-btn rounded-1"
                                >
                                    SPLIT BILL
                                </button>

                                <button
                                    type="button"
                                    onClick={() => addToast('Notice', 'Transfer Bill is active for Supervisors.', 'info')}
                                    className="pos-bottom-bar-btn rounded-1"
                                >
                                    TRANSFER BILL
                                </button>

                                <button
                                    type="button"
                                    onClick={() => addToast('Notice', 'Merge Orders is active for Supervisors.', 'info')}
                                    className="pos-bottom-bar-btn rounded-1"
                                >
                                    MERGE ORDERS
                                </button>
                            </div>
                        </div>
                    )}

                    {/* D. FAVORITE VIEW */}
                    {activeTopTab === 'FAVORITE' && (
                        <div className="flex-grow-1 p-3 overflow-y-auto">
                            <div className="fs-5 fw-bold text-dark mb-3">Favorite Quick-Add Items</div>
                            <div className="row g-2">
                                {displayedItems.map(item => (
                                    <div key={item.id} className="col-6 col-sm-4 col-md-3 col-xl-2">
                                        <div 
                                            className="pos-item-tile shadow-2xs h-100"
                                            onClick={() => addToCart(item)}
                                        >
                                            <div className="fw-bold text-dark text-truncate">{item.name}</div>
                                            <div className="d-flex align-items-center justify-content-between mt-2">
                                                <span className="badge bg-light text-secondary border small" style={{ fontSize: '9px' }}>
                                                    {item.preparation_department?.toUpperCase() || 'KITCHEN'}
                                                </span>
                                                <span className="font-mono text-primary fw-bold fs-6">
                                                    ETB {parseFloat(item.price).toFixed(2)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* E. SEARCH VIEW */}
                    {activeTopTab === 'SEARCH' && (
                        <div className="flex-grow-1 d-flex flex-column p-3 overflow-hidden">
                            <div className="mb-3">
                                <input
                                    type="text"
                                    autoFocus
                                    value={searchQuery}
                                    onChange={e => setSearchQuery(e.target.value)}
                                    placeholder="Type item name or code to search..."
                                    className="form-control form-control-lg border"
                                />
                            </div>

                            <div className="flex-grow-1 overflow-y-auto">
                                <div className="row g-2">
                                    {displayedItems.map(item => (
                                        <div key={item.id} className="col-6 col-sm-4 col-md-3 col-xl-2">
                                            <div 
                                                className="pos-item-tile shadow-2xs h-100"
                                                onClick={() => addToCart(item)}
                                            >
                                                <div>
                                                    <div className="fw-bold text-dark text-truncate">{item.name}</div>
                                                    <div className="text-muted small font-mono">{item.code}</div>
                                                </div>
                                                <div className="d-flex align-items-center justify-content-between mt-2">
                                                    <span className="badge bg-light text-secondary border small" style={{ fontSize: '9px' }}>
                                                        {item.category_name}
                                                    </span>
                                                    <span className="font-mono text-primary fw-bold fs-6">
                                                        ETB {parseFloat(item.price).toFixed(2)}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Floating Mobile Cart Pill (Only on mobile when viewing content) */}
            {mobileView === 'content' && cart.length > 0 && (
                <div className="d-md-none position-fixed bottom-0 start-0 end-0 p-2 z-2" style={{ pointerEvents: 'none' }}>
                    <button
                        type="button"
                        onClick={() => setMobileView('docket')}
                        className="btn btn-primary w-100 shadow-lg py-2.5 px-3 fw-bold d-flex align-items-center justify-content-between"
                        style={{ pointerEvents: 'auto', borderRadius: '12px' }}
                    >
                        <div className="d-flex align-items-center gap-2">
                            <span className="badge bg-white text-primary font-mono">{cartTotals.itemCount} Items</span>
                            <span>Open Docket</span>
                        </div>
                        <div className="font-mono fs-6 fw-bolder">
                            ETB {cartTotals.grandTotal.toFixed(2)} &rarr;
                        </div>
                    </button>
                </div>
            )}

            {/* 3. MODALS & POPUPS */}

            {/* Settle / Paid Modal (Ethiopian Birr Settlement) */}
            {showSettleModal && orderToSettle && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.6)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border shadow-lg">
                            <div className="modal-header bg-success text-white">
                                <h5 className="modal-title fs-6 fw-bold">
                                    <i className="bi bi-cash-stack me-1"></i> Settle / Mark Order Paid
                                </h5>
                                <button type="button" className="btn-close btn-close-white" onClick={() => setShowSettleModal(false)}></button>
                            </div>
                            <div className="modal-body p-4">
                                <div className="text-center mb-3">
                                    <span className="text-muted small text-uppercase">Total Due</span>
                                    <h2 className="fw-bolder font-mono text-success mb-0">
                                        ETB {parseFloat(orderToSettle.total_amount).toFixed(2)}
                                    </h2>
                                    <div className="small text-muted">
                                        Order #{orderToSettle.order_number} &bull; Table: <strong>{orderToSettle.table ? `T-${orderToSettle.table.table_number}` : 'Takeaway'}</strong>
                                    </div>
                                </div>

                                {/* Payment Method Selection */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold small text-dark">Select Payment Method</label>
                                    <div className="d-grid gap-2" style={{ gridTemplateColumns: '1fr 1fr' }}>
                                        {[
                                            { id: 'cash', label: 'Cash (Birr)', icon: 'bi-cash' },
                                            { id: 'telebirr', label: 'Telebirr', icon: 'bi-phone' },
                                            { id: 'cbe_birr', label: 'CBE Birr', icon: 'bi-bank' },
                                            { id: 'card', label: 'Card / POS', icon: 'bi-credit-card' },
                                        ].map(pm => (
                                            <button
                                                key={pm.id}
                                                type="button"
                                                onClick={() => setSettlePaymentMethod(pm.id)}
                                                className={`btn btn-sm p-2 text-start d-flex align-items-center gap-2 border fw-bold ${
                                                    settlePaymentMethod === pm.id ? 'btn-primary text-white' : 'btn-light text-dark'
                                                }`}
                                            >
                                                <i className={`bi ${pm.icon} fs-5`}></i>
                                                <span>{pm.label}</span>
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {/* Cash Tendered & Change */}
                                <div className="mb-3">
                                    <label className="form-label fw-bold small text-dark">Amount Tendered (Birr)</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        value={settleAmountTendered}
                                        onChange={e => setSettleAmountTendered(e.target.value)}
                                        placeholder={`ETB ${parseFloat(orderToSettle.total_amount).toFixed(2)}`}
                                        className="form-control font-mono fw-bold fs-5 text-end"
                                    />
                                    {/* Quick Denominations */}
                                    <div className="d-flex gap-1 mt-1.5 overflow-x-auto">
                                        <button 
                                            type="button" 
                                            onClick={() => setSettleAmountTendered(parseFloat(orderToSettle.total_amount).toFixed(2))} 
                                            className="btn btn-xs btn-outline-secondary"
                                        >
                                            Exact
                                        </button>
                                        {[50, 100, 200, 500, 1000].map(add => (
                                            <button 
                                                key={add}
                                                type="button" 
                                                onClick={() => setSettleAmountTendered((parseFloat(settleAmountTendered || 0) + add).toFixed(2))} 
                                                className="btn btn-xs btn-outline-secondary"
                                            >
                                                +{add}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {settleAmountTendered && parseFloat(settleAmountTendered) >= parseFloat(orderToSettle.total_amount) && (
                                    <div className="p-2.5 bg-light rounded-2 border mb-3 d-flex justify-content-between align-items-center">
                                        <span className="fw-bold text-muted">Change to Return:</span>
                                        <strong className="fs-5 text-primary font-mono">
                                            ETB {(parseFloat(settleAmountTendered) - parseFloat(orderToSettle.total_amount)).toFixed(2)}
                                        </strong>
                                    </div>
                                )}

                                {/* Thermal Receipt Checkbox */}
                                <div className="form-check form-switch mb-3">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="printReceiptToggle"
                                        checked={printReceiptOnSettle}
                                        onChange={e => setPrintReceiptOnSettle(e.target.checked)}
                                    />
                                    <label className="form-check-label small text-muted" htmlFor="printReceiptToggle">
                                        Print Settlement Receipt on Thermal Printer (IRP 200D)
                                    </label>
                                </div>

                                <button
                                    type="button"
                                    onClick={handleConfirmSettleOrder}
                                    disabled={isSubmitting}
                                    className="btn btn-success fw-bold w-100 py-2.5 fs-6 shadow-sm"
                                >
                                    {isSubmitting ? 'Processing Payment...' : 'Confirm Payment & Free Table'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Retrieve Order Modal */}
            {showRetrieveModal && selectedReferenceOrder && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.6)' }}>
                    <div className="modal-dialog modal-dialog-centered modal-lg">
                        <div className="modal-content border shadow-lg">
                            <div className="modal-header bg-light">
                                <h5 className="modal-title fs-6 text-dark fw-bold">
                                    Order #{selectedReferenceOrder.order_number} Details
                                </h5>
                                <button type="button" className="btn-close" onClick={() => setShowRetrieveModal(false)}></button>
                            </div>
                            <div className="modal-body p-3">
                                <div className="row g-2 mb-3 small bg-light p-2 rounded border">
                                    <div className="col-3">
                                        <span className="text-muted">Table:</span> <strong>{selectedReferenceOrder.table ? `T-${selectedReferenceOrder.table.table_number}` : 'Takeaway'}</strong>
                                    </div>
                                    <div className="col-3">
                                        <span className="text-muted">Waiter:</span> <strong>{selectedReferenceOrder.waiter ? selectedReferenceOrder.waiter.name : 'Staff'}</strong>
                                    </div>
                                    <div className="col-3">
                                        <span className="text-muted">Status:</span> 
                                        <span className={`badge ms-1 ${selectedReferenceOrder.status === 'completed' ? 'bg-success' : 'bg-warning text-dark'}`}>
                                            {selectedReferenceOrder.status === 'completed' ? 'PAID' : 'HELD'}
                                        </span>
                                    </div>
                                    <div className="col-3 text-end">
                                        <span className="text-muted">Total:</span> <strong className="text-primary font-mono fs-6">ETB {parseFloat(selectedReferenceOrder.total_amount).toFixed(2)}</strong>
                                    </div>
                                </div>

                                <div className="border rounded p-2 mb-3 bg-white" style={{ maxHeight: '200px', overflowY: 'auto' }}>
                                    <table className="table table-sm mb-0">
                                        <thead className="table-light">
                                            <tr>
                                                <th>Item</th>
                                                <th className="text-center">Qty</th>
                                                <th className="text-end">Price</th>
                                                <th className="text-end">Total (ETB)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {selectedReferenceOrder.items && selectedReferenceOrder.items.map(it => (
                                                <tr key={it.id}>
                                                    <td>{it.menu_item ? it.menu_item.name : 'Item'}</td>
                                                    <td className="text-center font-mono">{it.quantity}</td>
                                                    <td className="text-end font-mono">{parseFloat(it.unit_price).toFixed(2)}</td>
                                                    <td className="text-end font-mono fw-bold">{parseFloat(it.total_price || (it.quantity * it.unit_price)).toFixed(2)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div className="d-flex align-items-center gap-1.5">
                                        {/* 1-touch Add Items Button */}
                                        {selectedReferenceOrder.status !== 'completed' && (can('pos.orders.edit') || can('pos.orders.create')) && (
                                            <button
                                                type="button"
                                                onClick={() => handleLoadOrderToEdit(selectedReferenceOrder)}
                                                className="btn btn-primary btn-sm fw-bold"
                                            >
                                                <i className="bi bi-plus-circle me-1"></i>
                                                Add Products & Hold
                                            </button>
                                        )}

                                        {/* 1-touch Settle Button */}
                                        {selectedReferenceOrder.status !== 'completed' && (can('finance.view') || can('pos.orders.edit')) && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setOrderToSettle(selectedReferenceOrder);
                                                    setShowSettleModal(true);
                                                }}
                                                className="btn btn-success btn-sm fw-bold"
                                            >
                                                <i className="bi bi-cash-stack me-1"></i>
                                                Settle / Paid
                                            </button>
                                        )}
                                    </div>

                                    {/* Ticket Reprint */}
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (selectedReferenceOrder.tickets && selectedReferenceOrder.tickets.length > 0) {
                                                selectedReferenceOrder.tickets.forEach(ticket => {
                                                    const ticketHtml = buildIrp200dTicketHtml(selectedReferenceOrder, ticket, appName);
                                                    printIrp200d(ticketHtml);
                                                });
                                                addToast('Reprinted', 'Order tickets sent to IRP 200D', 'success');
                                            } else {
                                                addToast('No Tickets', 'No station tickets attached to this order', 'warning');
                                            }
                                        }}
                                        className="btn btn-outline-dark btn-sm fw-bold"
                                    >
                                        <i className="bi bi-printer-fill me-1"></i>
                                        Reprint to IRP 200D
                                    </button>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => setShowRetrieveModal(false)}>Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Item Notes Modal */}
            {itemNotesModal && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.6)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border shadow-md">
                            <div className="modal-header bg-light">
                                <h5 className="modal-title fs-6 fw-bold">
                                    Prep Notes: {itemNotesModal.name}
                                </h5>
                                <button type="button" className="btn-close" onClick={() => setItemNotesModal(null)}></button>
                            </div>
                            <div className="modal-body">
                                <label className="form-label text-muted small">Special Instructions (Kitchen / Bar Ticket)</label>
                                <textarea
                                    className="form-control font-mono"
                                    rows="3"
                                    defaultValue={itemNotesModal.notes}
                                    id="item-notes-textarea"
                                    placeholder="e.g. Less spicy, extra sauce, ice on side..."
                                ></textarea>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => setItemNotesModal(null)}>Cancel</button>
                                <button 
                                    type="button" 
                                    className="btn btn-primary btn-sm fw-bold"
                                    onClick={() => {
                                        const val = document.getElementById('item-notes-textarea').value;
                                        saveItemNotes(itemNotesModal.id, val);
                                    }}
                                >
                                    Save Note
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Exit POS Confirmation Modal */}
            {showExitModal && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.6)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border shadow-md">
                            <div className="modal-header bg-light">
                                <h5 className="modal-title fs-6 fw-bold">Exit POS Terminal</h5>
                                <button type="button" className="btn-close" onClick={() => setShowExitModal(false)}></button>
                            </div>
                            <div className="modal-body">
                                <p className="mb-0 text-muted">
                                    Are you sure you want to return to the Core ERP Dashboard? Any unsaved cart items will be cleared.
                                </p>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={() => setShowExitModal(false)}>Stay in POS</button>
                                <a href={routes.dashboard || '/'} className="btn btn-danger btn-sm fw-bold">
                                    Exit to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* 4. FLOATING TOAST NOTIFICATIONS */}
            <div 
                className="position-fixed bottom-0 end-0 p-3 z-3 d-flex flex-column gap-2"
                style={{ maxWidth: '340px', pointerEvents: 'none' }}
            >
                {toasts.map(toast => (
                    <div 
                        key={toast.id}
                        className={`toast show border-0 shadow-lg text-white ${
                            toast.type === 'danger' ? 'bg-danger' :
                            toast.type === 'warning' ? 'bg-warning text-dark' :
                            toast.type === 'info' ? 'bg-info text-dark' :
                            'bg-success'
                        }`}
                        role="alert"
                        style={{ pointerEvents: 'auto', borderRadius: '6px' }}
                    >
                        <div className="d-flex align-items-center justify-content-between p-2.5">
                            <div className="d-flex align-items-center gap-2">
                                <i className={`bi fs-5 ${
                                    toast.type === 'danger' ? 'bi-x-circle-fill' :
                                    toast.type === 'warning' ? 'bi-exclamation-triangle-fill' :
                                    toast.type === 'info' ? 'bi-info-circle-fill' :
                                    'bi-check-circle-fill'
                                }`}></i>
                                <div>
                                    <div className="fw-bold small lh-1">{toast.title}</div>
                                    <div className="small opacity-90">{toast.message}</div>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                className="btn-close btn-close-white ms-2"
                                onClick={() => removeToast(toast.id)}
                            ></button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
