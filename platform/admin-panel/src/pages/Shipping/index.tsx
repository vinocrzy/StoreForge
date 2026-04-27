import { useState } from 'react';
import {
  useGetShippingMethodsQuery,
  useCreateShippingMethodMutation,
  useUpdateShippingMethodMutation,
  useDeleteShippingMethodMutation,
  type ShippingMethod,
  type ShippingMethodPayload,
} from '../../services/shipping';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import { Modal } from '../../components/ui/modal';

const TYPE_LABELS: Record<ShippingMethod['type'], string> = {
  flat_rate: 'Flat Rate',
  weight_based: 'Weight Based',
  free_above: 'Free Above Threshold',
  local_pickup: 'Local Pickup',
};

const EMPTY_FORM: ShippingMethodPayload = {
  name: '',
  type: 'flat_rate',
  rate: null,
  free_above: null,
  is_active: true,
  display_order: 0,
};

export default function ShippingMethodsPage() {
  const { data, isLoading, error } = useGetShippingMethodsQuery();
  const [createMethod, { isLoading: isCreating }] = useCreateShippingMethodMutation();
  const [updateMethod, { isLoading: isUpdating }] = useUpdateShippingMethodMutation();
  const [deleteMethod] = useDeleteShippingMethodMutation();

  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editTarget, setEditTarget] = useState<ShippingMethod | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<ShippingMethod | null>(null);
  const [form, setForm] = useState<ShippingMethodPayload>(EMPTY_FORM);

  const showAlert = (variant: 'success' | 'error', title: string, message: string) => {
    setAlert({ variant, title, message });
    setTimeout(() => setAlert(null), 4000);
  };

  const openCreate = () => {
    setForm(EMPTY_FORM);
    setEditTarget(null);
    setShowForm(true);
  };

  const openEdit = (m: ShippingMethod) => {
    setForm({ name: m.name, type: m.type, rate: m.rate, free_above: m.free_above, is_active: m.is_active, display_order: m.display_order });
    setEditTarget(m);
    setShowForm(true);
  };

  const handleSave = async () => {
    try {
      if (editTarget) {
        await updateMethod({ id: editTarget.id, ...form }).unwrap();
        showAlert('success', 'Updated', 'Shipping method updated.');
      } else {
        await createMethod(form).unwrap();
        showAlert('success', 'Created', 'Shipping method created.');
      }
      setShowForm(false);
    } catch {
      showAlert('error', 'Error', 'Failed to save shipping method.');
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    try {
      await deleteMethod(deleteTarget.id).unwrap();
      showAlert('success', 'Deleted', `"${deleteTarget.name}" deleted.`);
      setDeleteTarget(null);
    } catch {
      showAlert('error', 'Error', 'Failed to delete.');
    }
  };

  const inputCls = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';

  if (isLoading) return <div className="p-6 text-center text-gray-500">Loading...</div>;

  const methods = data?.data ?? [];

  return (
    <div className="p-6">
      {alert && <div className="mb-4"><Alert variant={alert.variant} title={alert.title} message={alert.message} /></div>}

      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Shipping Methods</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure how orders are shipped</p>
        </div>
        <Button variant="primary" onClick={openCreate}>Add Method</Button>
      </div>

      {error && <Alert variant="error" title="Error" message="Failed to load shipping methods." />}

      {methods.length === 0 && !error ? (
        <div className="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-12 text-center">
          <p className="text-gray-500 dark:text-gray-400 mb-4">No shipping methods configured yet.</p>
          <Button variant="primary" onClick={openCreate}>Add Your First Method</Button>
        </div>
      ) : (
        <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                {['Name', 'Type', 'Rate', 'Status', 'Actions'].map((h) => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
              {methods.map((m) => (
                <tr key={m.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                  <td className="px-4 py-3 font-medium text-gray-900 dark:text-white">{m.name}</td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{TYPE_LABELS[m.type]}</td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-300">
                    {m.type === 'local_pickup' ? 'Free' :
                     m.type === 'free_above' ? `Free over $${m.free_above}` :
                     m.rate != null ? `$${Number(m.rate).toFixed(2)}` : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                      m.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                    }`}>
                      {m.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex gap-2">
                      <Button size="sm" variant="outline" onClick={() => openEdit(m)}>Edit</Button>
                      <Button size="sm" variant="outline" onClick={() => setDeleteTarget(m)}
                        className="text-red-600 border-red-300 hover:bg-red-50">Delete</Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Create/Edit Modal */}
      <Modal isOpen={showForm} onClose={() => setShowForm(false)} className="max-w-lg w-full">
        <div className="p-6">
          <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-6">
            {editTarget ? 'Edit Shipping Method' : 'New Shipping Method'}
          </h2>
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
              <input className={inputCls} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="e.g. Standard Shipping" />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Type *</label>
              <select className={inputCls} value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value as ShippingMethod['type'] })}>
                {Object.entries(TYPE_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
            </div>
            {(form.type === 'flat_rate' || form.type === 'weight_based') && (
              <div>
                <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {form.type === 'weight_based' ? 'Rate per kg ($)' : 'Rate ($)'}
                </label>
                <input type="number" min="0" step="0.01" className={inputCls}
                  value={form.rate ?? ''} onChange={(e) => setForm({ ...form, rate: e.target.value ? parseFloat(e.target.value) : null })} />
              </div>
            )}
            {form.type === 'free_above' && (
              <>
                <div>
                  <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Free above amount ($)</label>
                  <input type="number" min="0" step="0.01" className={inputCls}
                    value={form.free_above ?? ''} onChange={(e) => setForm({ ...form, free_above: e.target.value ? parseFloat(e.target.value) : null })} />
                </div>
                <div>
                  <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Standard rate below threshold ($)</label>
                  <input type="number" min="0" step="0.01" className={inputCls}
                    value={form.rate ?? ''} onChange={(e) => setForm({ ...form, rate: e.target.value ? parseFloat(e.target.value) : null })} />
                </div>
              </>
            )}
            <div className="flex items-center gap-3">
              <input type="checkbox" id="isActive" checked={!!form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                className="w-4 h-4 accent-brand-600" />
              <label htmlFor="isActive" className="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
            </div>
          </div>
          <div className="flex gap-3 justify-end mt-6">
            <Button variant="outline" onClick={() => setShowForm(false)}>Cancel</Button>
            <Button variant="primary" onClick={handleSave} disabled={isCreating || isUpdating}>
              {editTarget ? 'Save Changes' : 'Create Method'}
            </Button>
          </div>
        </div>
      </Modal>

      {/* Delete confirmation */}
      <Modal isOpen={!!deleteTarget} onClose={() => setDeleteTarget(null)} className="max-w-sm w-full">
        <div className="p-6">
          <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-2">Delete Shipping Method</h2>
          <p className="text-gray-600 dark:text-gray-300 mb-6">
            Delete <strong>"{deleteTarget?.name}"</strong>? This cannot be undone.
          </p>
          <div className="flex gap-3 justify-end">
            <Button variant="outline" onClick={() => setDeleteTarget(null)}>Cancel</Button>
            <Button variant="primary" className="bg-red-600 hover:bg-red-700 border-red-600" onClick={handleDelete}>Delete</Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
