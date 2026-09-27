export const commonTranslations = {
    // Actions
    save: { bn: 'সংরক্ষণ করুন', en: 'Save' },
    cancel: { bn: 'বাতিল', en: 'Cancel' },
    delete: { bn: 'মুছুন', en: 'Delete' },
    edit: { bn: 'এডিট', en: 'Edit' },
    view: { bn: 'বিস্তারিত', en: 'View' },
    print: { bn: 'প্রিন্ট', en: 'Print' },
    download: { bn: 'ডাউনলোড', en: 'Download' },
    search: { bn: 'খুঁজুন...', en: 'Search...' },
    filter: { bn: 'ফিল্টার', en: 'Filter' },
    reset: { bn: 'রিসেট', en: 'Reset' },
    refresh: { bn: 'রিফ্রেশ', en: 'Refresh' },
    add_new: { bn: 'নতুন যোগ করুন', en: 'Add New' },
    back: { bn: 'ফিরে যান', en: 'Back' },
    close: { bn: 'বন্ধ করুন', en: 'Close' },
    confirm: { bn: 'নিশ্চিত করুন', en: 'Confirm' },
    select: { bn: 'বাছাই করুন', en: 'Select' },

    // Status
    status: { bn: 'স্ট্যাটাস', en: 'Status' },
    active: { bn: 'সক্রিয়', en: 'Active' },
    inactive: { bn: 'নিষ্ক্রিয়', en: 'Inactive' },
    paid: { bn: 'পরিশোধিত', en: 'Paid' },
    due: { bn: 'বাকি', en: 'Due' },
    partial: { bn: 'আংশিক', en: 'Partial' },
    pending: { bn: 'অপেক্ষমাণ', en: 'Pending' },
    approved: { bn: 'অনুমোদিত', en: 'Approved' },
    rejected: { bn: 'বাতিলকৃত', en: 'Rejected' },

    // Common Retail Labels
    total: { bn: 'মোট', en: 'Total' },
    subtotal: { bn: 'সাবটোটাল', en: 'Subtotal' },
    discount: { bn: 'ডিসকাউন্ট', en: 'Discount' },
    tax: { bn: 'ভ্যাট / ট্যাক্স', en: 'Tax / VAT' },
    grand_total: { bn: 'সর্বমোট', en: 'Grand Total' },
    paid_amount: { bn: 'পরিশোধ', en: 'Paid Amount' },
    due_amount: { bn: 'বাকি', en: 'Due Amount' },
    change_amount: { bn: 'ফেরত টাকা', en: 'Change Amount' },
    quantity: { bn: 'পরিমাণ', en: 'Quantity' },
    price: { bn: 'মূল্য', en: 'Price' },
    buy_price: { bn: 'ক্রয়মূল্য', en: 'Buy Price' },
    sell_price: { bn: 'বিক্রয়মূল্য', en: 'Sell Price' },
    stock: { bn: 'স্টক', en: 'Stock' },
    customer: { bn: 'কাস্টমার', en: 'Customer' },
    supplier: { bn: 'সাপ্লায়ার', en: 'Supplier' },
    date: { bn: 'তারিখ', en: 'Date' },
    invoice_no: { bn: 'ইনভয়েস নং', en: 'Invoice No' },
    payment_method: { bn: 'পেমেন্ট মাধ্যম', en: 'Payment Method' },
    cash: { bn: 'নগদ / ক্যাশ', en: 'Cash' },
    bank: { bn: 'ব্যাংক', en: 'Bank' },
    mobile_banking: { bn: 'মোবাইল ব্যাংকিং', en: 'Mobile Banking' },
    notes: { bn: 'মন্তব্য', en: 'Notes' },
    action: { bn: 'অ্যাকশন', en: 'Action' },
    all: { bn: 'সকল', en: 'All' },
    today: { bn: 'আজ', en: 'Today' },
    this_month: { bn: 'এই মাস', en: 'This Month' },
    last_30_days: { bn: 'গত ৩০ দিন', en: 'Last 30 Days' }
}

/**
 * Format a number with BDT currency symbol
 */
export function formatCurrencyBDT(amount, lang = 'bn') {
    const val = Number(amount || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
    return `৳ ${val}`
}
