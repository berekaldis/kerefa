/**
 * Thermal Printer ESC/POS 80mm Formatting Utility
 * Hardware Target: Model IRP 200D (Thermal Receipt / Order Printer)
 */

export function buildIrp200dTicketHtml(order, ticket, appName = 'KEREFA RESTAURANT') {
    const stationName = ticket.station ? ticket.station.name : (ticket.ticket_type ? ticket.ticket_type.toUpperCase() + ' STATION' : 'ORDER STATION');
    const printerModel = ticket.station ? (ticket.station.printer_model || 'IRP 200D') : 'IRP 200D';
    const tableName = order.table ? ('T-' + order.table.table_number) : (order.order_type ? order.order_type.toUpperCase() : 'ORDER');
    const waiterName = order.waiter ? order.waiter.name : 'Staff';
    const nowStr = new Date().toLocaleString();

    let itemsHtml = '';
    if (ticket.items && ticket.items.length > 0) {
        ticket.items.forEach(ti => {
            const name = ti.order_item?.menu_item?.name || 'Item';
            const notes = ti.notes ? `<div class="irp200d-item-notes">** ${ti.notes} **</div>` : '';
            itemsHtml += `
                <tr>
                    <td class="irp200d-item-qty">${ti.quantity}</td>
                    <td class="irp200d-item-name">${name}${notes}</td>
                </tr>
            `;
        });
    }

    return `
        <div class="irp200d-ticket-header">
            <div class="irp200d-title">${appName.toUpperCase()}</div>
            <div style="font-size: 11px; margin: 2px 0;">PREPARATION ORDER TICKET</div>
            <div class="irp200d-banner">*** ${stationName.toUpperCase()} ***</div>
        </div>
        <div class="irp200d-meta-row">
            <span>ORDER: <strong>${order.order_number}</strong></span>
            <span>TICKET: <strong>#${ticket.ticket_number}</strong></span>
        </div>
        <div class="irp200d-meta-row">
            <span>TABLE: <strong>${tableName}</strong></span>
            <span>WAITER: ${waiterName}</span>
        </div>
        <div class="irp200d-meta-row">
            <span>TIME: ${nowStr}</span>
            <span>PRINTER: ${printerModel}</span>
        </div>
        <table class="irp200d-items-table">
            <thead>
                <tr>
                    <th style="width: 28px;">QTY</th>
                    <th>ITEM / SPECIAL PREPARATION</th>
                </tr>
            </thead>
            <tbody>
                ${itemsHtml}
            </tbody>
        </table>
        <div class="irp200d-cutter-feed">
            [ TEAR TICKET / IRP 200D AUTO CUT ]
        </div>
    `;
}

export function printIrp200d(ticketHtml) {
    if (window.printIrp200dTicket) {
        window.printIrp200dTicket(ticketHtml);
    } else {
        console.warn('printIrp200dTicket utility not mounted on window');
    }
}
