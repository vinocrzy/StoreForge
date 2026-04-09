import { useState, useEffect } from 'react';
import { useGetAllSettingsQuery, useUpdateSettingsMutation } from '../../services/settings';
import type { SettingsGroupName, UpdateSettingsPayload } from '../../services/settings';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';

// ---------------------------------------------------------------------------
// Tab definitions
// ---------------------------------------------------------------------------
const TABS: { id: SettingsGroupName; label: string }[] = [
  { id: 'general', label: 'General' },
  { id: 'branding', label: 'Branding' },
  { id: 'policies', label: 'Policies' },
  { id: 'checkout', label: 'Checkout' },
  { id: 'payments', label: 'Payments' },
  { id: 'shipping', label: 'Shipping' },
  { id: 'seo', label: 'SEO' },
  { id: 'notifications', label: 'Notifications' },
  { id: 'security', label: 'Security' },
];

// ---------------------------------------------------------------------------
// Reusable field components
// ---------------------------------------------------------------------------
interface FieldProps {
  label: string;
  description?: string | null;
  children: React.ReactNode;
}

const Field = ({ label, description, children }: FieldProps) => (
  <div className="grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
    <div>
      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{label}</label>
      {description && <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-500">{description}</p>}
    </div>
    <div className="sm:col-span-2">{children}</div>
  </div>
);

const inputClass =
  'w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-sm text-dark placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white';

const textareaClass = `${inputClass} min-h-[120px] resize-y`;

interface TextInputProps {
  value: string;
  onChange: (v: string) => void;
  placeholder?: string;
  type?: string;
}
const TextInput = ({ value, onChange, placeholder, type = 'text' }: TextInputProps) => (
  <input type={type} className={inputClass} value={value} placeholder={placeholder} onChange={(e) => onChange(e.target.value)} />
);

interface ToggleProps {
  checked: boolean;
  onChange: (v: boolean) => void;
  label?: string;
}
const Toggle = ({ checked, onChange, label }: ToggleProps) => (
  <label className="flex cursor-pointer items-center gap-3">
    <div className="relative">
      <input type="checkbox" className="sr-only" checked={checked} onChange={(e) => onChange(e.target.checked)} />
      <div className={`h-6 w-11 rounded-full transition-colors ${checked ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-600'}`} />
      <div className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform ${checked ? 'left-5' : 'left-0.5'}`} />
    </div>
    {label && <span className="text-sm text-gray-700 dark:text-gray-300">{label}</span>}
  </label>
);

// ---------------------------------------------------------------------------
// Main Component
// ---------------------------------------------------------------------------
const StoreSettingsPage = () => {
  const [activeTab, setActiveTab] = useState<SettingsGroupName>('general');
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);
  const [dirty, setDirty] = useState(false);

  const { data: settingsData, isLoading } = useGetAllSettingsQuery();
  const [updateSettings, { isLoading: isSaving }] = useUpdateSettingsMutation();

  const [form, setForm] = useState<Record<string, Record<string, string | boolean | number>>>({});

  useEffect(() => {
    if (!settingsData?.data) return;
    const initial: Record<string, Record<string, string | boolean | number>> = {};
    for (const [group, fields] of Object.entries(settingsData.data)) {
      initial[group] = {};
      for (const [key, field] of Object.entries(fields as Record<string, { value: unknown }>)) {
        initial[group][key] = field.value as string | boolean | number;
      }
    }
    setForm(initial);
    setDirty(false);
  }, [settingsData]);

  const set = (group: string, key: string, value: string | boolean | number) => {
    setForm((prev) => ({ ...prev, [group]: { ...prev[group], [key]: value } }));
    setDirty(true);
  };

  const g = (group: string) => form[group] ?? {};

  const handleSave = async () => {
    const payload: UpdateSettingsPayload = {};
    for (const tab of TABS) {
      if (form[tab.id]) (payload as Record<string, unknown>)[tab.id] = form[tab.id];
    }
    try {
      await updateSettings(payload).unwrap();
      setAlert({ variant: 'success', title: 'Saved', message: 'Settings saved successfully.' });
      setDirty(false);
    } catch {
      setAlert({ variant: 'error', title: 'Error', message: 'Failed to save settings. Please try again.' });
    }
    setTimeout(() => setAlert(null), 4000);
  };

  if (isLoading) return <div className="p-12 text-center text-body">Loading settings...</div>;

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex items-start justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Store Settings</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure your store preferences and policies</p>
        </div>
        <Button variant="primary" onClick={handleSave} disabled={isSaving || !dirty}>
          {isSaving ? 'Saving...' : 'Save Changes'}
        </Button>
      </div>

      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      {/* Tab bar */}
      <div className="mb-6 flex flex-wrap gap-1 border-b border-stroke dark:border-strokedark">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`px-4 py-2.5 text-sm font-medium transition-colors ${
              activeTab === tab.id
                ? 'border-b-2 border-primary text-primary'
                : 'text-body hover:text-dark dark:hover:text-white'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Panel */}
      <div className="rounded-xl border border-stroke bg-white p-6 shadow-sm dark:border-strokedark dark:bg-boxdark">

        {activeTab === 'general' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">General Information</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Store Name" description="Display name shown to customers">
              <TextInput value={String(g('general').store_name ?? '')} onChange={(v) => set('general', 'store_name', v)} placeholder="My Store" />
            </Field>
            <Field label="Store Description" description="Short description of your store">
              <textarea className={textareaClass} value={String(g('general').store_description ?? '')} onChange={(e) => set('general', 'store_description', e.target.value)} placeholder="We sell..." />
            </Field>
            <Field label="Contact Email" description="Customer support email">
              <TextInput type="email" value={String(g('general').store_email ?? '')} onChange={(v) => set('general', 'store_email', v)} placeholder="hello@mystore.com" />
            </Field>
            <Field label="Contact Phone" description="Contact phone number">
              <TextInput value={String(g('general').store_phone ?? '')} onChange={(v) => set('general', 'store_phone', v)} placeholder="+1 555 000 0000" />
            </Field>
            <Field label="Address" description="Physical store address">
              <TextInput value={String(g('general').store_address ?? '')} onChange={(v) => set('general', 'store_address', v)} placeholder="123 Main St, City, Country" />
            </Field>
            <Field label="Currency" description="ISO currency code">
              <select className={inputClass} value={String(g('general').currency ?? 'USD')} onChange={(e) => set('general', 'currency', e.target.value)}>
                {['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'INR', 'AED', 'SAR'].map((c) => <option key={c} value={c}>{c}</option>)}
              </select>
            </Field>
            <Field label="Timezone" description="Store timezone">
              <select className={inputClass} value={String(g('general').timezone ?? 'UTC')} onChange={(e) => set('general', 'timezone', e.target.value)}>
                {['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Tokyo', 'Australia/Sydney'].map((tz) => <option key={tz} value={tz}>{tz}</option>)}
              </select>
            </Field>
            <Field label="Logo URL" description="Full URL to your store logo">
              <TextInput value={String(g('general').logo_url ?? '')} onChange={(v) => set('general', 'logo_url', v)} placeholder="https://..." />
            </Field>
            <Field label="Favicon URL" description="Favicon URL (32x32 .ico or .png)">
              <TextInput value={String(g('general').favicon_url ?? '')} onChange={(v) => set('general', 'favicon_url', v)} placeholder="https://..." />
            </Field>
          </div>
        )}

        {activeTab === 'branding' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Brand Colors & Typography</h2>
            <hr className="border-stroke dark:border-strokedark" />
            {[
              { key: 'primary_color', label: 'Primary Color', default: '#3C50E0', desc: 'Main brand color for buttons and links' },
              { key: 'secondary_color', label: 'Secondary Color', default: '#64748B', desc: 'Secondary elements' },
              { key: 'accent_color', label: 'Accent Color', default: '#10B981', desc: 'Highlights and badges' },
            ].map(({ key, label, default: def, desc }) => (
              <Field key={key} label={label} description={desc}>
                <div className="flex items-center gap-3">
                  <input type="color" className="h-10 w-16 cursor-pointer rounded border border-stroke dark:border-strokedark" value={String(g('branding')[key] ?? def)} onChange={(e) => set('branding', key, e.target.value)} />
                  <TextInput value={String(g('branding')[key] ?? def)} onChange={(v) => set('branding', key, v)} />
                </div>
              </Field>
            ))}
            <Field label="Font Family" description="Primary storefront font">
              <select className={inputClass} value={String(g('branding').font_family ?? 'Inter')} onChange={(e) => set('branding', 'font_family', e.target.value)}>
                {['Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins', 'Montserrat', 'Nunito', 'Raleway', 'Playfair Display', 'Merriweather'].map((f) => <option key={f} value={f}>{f}</option>)}
              </select>
            </Field>
            <div>
              <p className="mb-2 text-xs font-medium uppercase tracking-wider text-gray-500">Preview</p>
              <div className="flex items-center gap-4 rounded-lg border border-stroke p-4 dark:border-strokedark" style={{ fontFamily: String(g('branding').font_family ?? 'Inter') }}>
                <div className="flex h-10 w-10 items-center justify-center rounded-full text-white" style={{ backgroundColor: String(g('branding').primary_color ?? '#3C50E0') }}>S</div>
                <div>
                  <p className="font-semibold" style={{ color: String(g('branding').primary_color ?? '#3C50E0') }}>{String(g('general').store_name ?? 'My Store')}</p>
                  <p className="text-xs" style={{ color: String(g('branding').secondary_color ?? '#64748B') }}>Brand preview</p>
                </div>
                <button className="ml-auto rounded-md px-4 py-1.5 text-sm text-white" style={{ backgroundColor: String(g('branding').accent_color ?? '#10B981') }}>Shop Now</button>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'policies' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Store Policies</h2>
            <hr className="border-stroke dark:border-strokedark" />
            {[
              { key: 'return_policy', label: 'Return Policy', placeholder: 'Returns accepted within 30 days...' },
              { key: 'privacy_policy', label: 'Privacy Policy', placeholder: 'We respect your privacy...' },
              { key: 'terms_of_service', label: 'Terms of Service', placeholder: 'By using this site...' },
            ].map(({ key, label, placeholder }) => (
              <Field key={key} label={label}>
                <textarea className={`${textareaClass} min-h-[180px]`} value={String(g('policies')[key] ?? '')} onChange={(e) => set('policies', key, e.target.value)} placeholder={placeholder} />
              </Field>
            ))}
          </div>
        )}

        {activeTab === 'checkout' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Checkout Configuration</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Guest Checkout" description="Allow customers to purchase without an account">
              <Toggle checked={Boolean(g('checkout').allow_guest_checkout ?? true)} onChange={(v) => set('checkout', 'allow_guest_checkout', v)} label="Allow guest checkout" />
            </Field>
            <Field label="Require Phone" description="Require phone number at checkout">
              <Toggle checked={Boolean(g('checkout').require_phone ?? true)} onChange={(v) => set('checkout', 'require_phone', v)} label="Phone number required" />
            </Field>
            <Field label="Require Account" description="Force account creation before checkout">
              <Toggle checked={Boolean(g('checkout').require_account ?? false)} onChange={(v) => set('checkout', 'require_account', v)} label="Account required" />
            </Field>
            <Field label="Minimum Order Amount" description="Minimum cart total (0 = no minimum)">
              <div className="flex items-center gap-2">
                <span className="text-sm text-body">{String(g('general').currency ?? 'USD')}</span>
                <input type="number" min={0} className={inputClass} value={Number(g('checkout').min_order_amount ?? 0)} onChange={(e) => set('checkout', 'min_order_amount', Number(e.target.value))} />
              </div>
            </Field>
          </div>
        )}

        {activeTab === 'payments' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Payment Methods</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Manual Payment" description="Bank transfer, cheque, etc.">
              <Toggle checked={Boolean(g('payments').manual_payment_enabled ?? true)} onChange={(v) => set('payments', 'manual_payment_enabled', v)} label="Enable manual payments" />
            </Field>
            {Boolean(g('payments').manual_payment_enabled ?? true) && (
              <Field label="Payment Instructions" description="Shown to customer after placing order">
                <textarea className={`${textareaClass} min-h-[120px]`} value={String(g('payments').manual_payment_instructions ?? '')} onChange={(e) => set('payments', 'manual_payment_instructions', e.target.value)} placeholder="Please transfer to: Bank XYZ, Account 123456, Reference: order number" />
              </Field>
            )}
            <Field label="Cash on Delivery" description="Allow payment at delivery">
              <Toggle checked={Boolean(g('payments').cod_enabled ?? false)} onChange={(v) => set('payments', 'cod_enabled', v)} label="Enable cash on delivery" />
            </Field>
          </div>
        )}

        {activeTab === 'shipping' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Shipping</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Free Shipping Threshold" description="Cart total that qualifies for free shipping (0 = never)">
              <div className="flex items-center gap-2">
                <span className="text-sm text-body">{String(g('general').currency ?? 'USD')}</span>
                <input type="number" min={0} className={inputClass} value={Number(g('shipping').free_shipping_threshold ?? 0)} onChange={(e) => set('shipping', 'free_shipping_threshold', Number(e.target.value))} />
              </div>
            </Field>
            <Field label="Flat Rate Shipping" description="Charge a fixed fee for all orders">
              <Toggle checked={Boolean(g('shipping').flat_rate_enabled ?? false)} onChange={(v) => set('shipping', 'flat_rate_enabled', v)} label="Enable flat rate" />
            </Field>
            {Boolean(g('shipping').flat_rate_enabled ?? false) && (
              <Field label="Flat Rate Cost" description="Shipping cost per order">
                <div className="flex items-center gap-2">
                  <span className="text-sm text-body">{String(g('general').currency ?? 'USD')}</span>
                  <input type="number" min={0} className={inputClass} value={Number(g('shipping').flat_rate_cost ?? 0)} onChange={(e) => set('shipping', 'flat_rate_cost', Number(e.target.value))} />
                </div>
              </Field>
            )}
          </div>
        )}

        {activeTab === 'seo' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">SEO & Analytics</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Default Meta Title" description="Fallback page title (50-60 characters)">
              <TextInput value={String(g('seo').meta_title ?? '')} onChange={(v) => set('seo', 'meta_title', v)} placeholder="My Store — Best Products Online" />
              <p className="mt-1 text-xs text-gray-400">{String(g('seo').meta_title ?? '').length} / 60 chars</p>
            </Field>
            <Field label="Default Meta Description" description="Fallback description (150-160 characters)">
              <textarea className={textareaClass} value={String(g('seo').meta_description ?? '')} onChange={(e) => set('seo', 'meta_description', e.target.value)} placeholder="Discover quality products..." />
              <p className="mt-1 text-xs text-gray-400">{String(g('seo').meta_description ?? '').length} / 160 chars</p>
            </Field>
            <Field label="Meta Keywords" description="Comma-separated keywords (optional)">
              <TextInput value={String(g('seo').meta_keywords ?? '')} onChange={(v) => set('seo', 'meta_keywords', v)} placeholder="shop, products, online store" />
            </Field>
            <Field label="Google Analytics ID" description="GA4 measurement ID">
              <TextInput value={String(g('seo').google_analytics ?? '')} onChange={(v) => set('seo', 'google_analytics', v)} placeholder="G-XXXXXXXXXX" />
            </Field>
            <Field label="Facebook Pixel ID" description="Meta Pixel ID for conversion tracking">
              <TextInput value={String(g('seo').facebook_pixel ?? '')} onChange={(v) => set('seo', 'facebook_pixel', v)} placeholder="000000000000000" />
            </Field>
          </div>
        )}

        {activeTab === 'notifications' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Admin Email" description="Receives admin notifications">
              <TextInput type="email" value={String(g('notifications').admin_email ?? '')} onChange={(v) => set('notifications', 'admin_email', v)} placeholder="admin@mystore.com" />
            </Field>
            <Field label="Order Confirmation" description="Email customers when order is placed">
              <Toggle checked={Boolean(g('notifications').order_confirmation_email ?? true)} onChange={(v) => set('notifications', 'order_confirmation_email', v)} label="Send order confirmation emails" />
            </Field>
            <Field label="Shipment Notifications" description="Email customers when order ships">
              <Toggle checked={Boolean(g('notifications').order_shipped_email ?? true)} onChange={(v) => set('notifications', 'order_shipped_email', v)} label="Send shipment notification emails" />
            </Field>
            <Field label="Low Stock Alerts" description="Email admin when stock falls below threshold">
              <Toggle checked={Boolean(g('notifications').low_stock_email ?? true)} onChange={(v) => set('notifications', 'low_stock_email', v)} label="Send low stock alert emails" />
            </Field>
            <Field label="Low Stock Threshold" description="Quantity that triggers a low stock alert">
              <input type="number" min={1} className={inputClass} value={Number(g('notifications').low_stock_threshold ?? 10)} onChange={(e) => set('notifications', 'low_stock_threshold', Number(e.target.value))} />
            </Field>
          </div>
        )}

        {activeTab === 'security' && (
          <div className="space-y-6">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Security</h2>
            <hr className="border-stroke dark:border-strokedark" />
            <Field label="Session Timeout" description="Idle minutes before automatic logout">
              <div className="flex items-center gap-2">
                <input type="number" min={5} max={1440} className={inputClass} value={Number(g('security').session_timeout_minutes ?? 60)} onChange={(e) => set('security', 'session_timeout_minutes', Number(e.target.value))} />
                <span className="text-sm text-body">minutes</span>
              </div>
            </Field>
            <Field label="Max Login Attempts" description="Failed attempts before account is temporarily locked">
              <input type="number" min={3} max={20} className={inputClass} value={Number(g('security').max_login_attempts ?? 5)} onChange={(e) => set('security', 'max_login_attempts', Number(e.target.value))} />
            </Field>
          </div>
        )}

      </div>

      {/* Sticky save bar */}
      {dirty && (
        <div className="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-between border-t border-stroke bg-white px-6 py-3 shadow-lg dark:border-strokedark dark:bg-boxdark">
          <p className="text-sm text-body">You have unsaved changes.</p>
          <div className="flex gap-3">
            <Button variant="outline" onClick={() => { setDirty(false); window.location.reload(); }}>Discard</Button>
            <Button variant="primary" onClick={handleSave} disabled={isSaving}>{isSaving ? 'Saving...' : 'Save Changes'}</Button>
          </div>
        </div>
      )}
    </div>
  );
};

export default StoreSettingsPage;
