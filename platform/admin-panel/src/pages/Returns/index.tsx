import { useState } from 'react';
import {
  useGetReturnsQuery,
  useApproveReturnMutation,
  useRejectReturnMutation,
  useProcessRefundMutation,
  type ReturnRequest,
} from '../../services/returns';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import { Modal } from '../../components/ui/modal';

const STATUS_BADGE: Record<ReturnRequest['status'], 'warning' | 'success' | 'error' | 'info' | 'light'> = {
  requested: 'warning',
  approved: 'info',
  rejected: 'error',
  received: 'info',
  refunded: 'success',
};

const REASON_LABELS: Record<string, string> = {
  damaged: 'Damaged',
  wrong_item: 'Wrong Item',
  not_as_described: 'Not As Described',
  changed_mind: 'Changed Mind',
  other: 'Other',
};

const fmtCurrency = (v: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v);

export default function ReturnsPage() {
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [selected, setSelected] = useState<ReturnRequest | null>(null);
  const [approveForm, setApproveForm] = useState({ refund_amount: 0, admin_notes: '' });
  const [rejectNotes, setRejectNotes] = useState('');
  const [action, setAction] = useState<'approve' | 'reject' | null>(null);
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);

  const { data, isLoading, error } = useGetReturnsQuery({ page, status: statusFilter || undefined });
  const [approveReturn, { isLoading: isApproving }] = useApproveReturnMutation();
  const [rejectReturn, { isLoading: isRejecting }] = useRejectReturnMutation();
  const [processRefund, { isLoading: isRefunding }] = useProcessRefundMutation();

  const showAlert = (variant: 'success' | 'error', title: string, message: string) => {
    setAlert({ variant, title, message });
    setTimeout(() => setAlert(null), 4000);
  };

  const handleApprove = async () => {
    if (!selected) return;
    try {
      await approveReturn({ id: selected.id, ...approveForm }).unwrap();
      showAlert('success', 'Approved', `Return ${selected.return_number} approved.`);
      setAction(null);
      setSelected(null);
    } catch {
      showAlert('error', 'Error', 'Failed to approve return.');
    }
  };

  const handleReject = async () => {
    if (!selected) return;
    try {
      await rejectReturn({ id: selected.id, admin_notes: rejectNotes }).unwrap();
      showAlert('success', 'Rejected', `Return ${selected.return_number} rejected.`);
      setAction(null);
      setSelected(null);
    } catch {
      showAlert('error', 'Error', 'Failed to reject return.');
    }
  };

  const handleRefund = async (ret: ReturnRequest) => {
    try {
      const res = await processRefund(ret.id).unwrap();
      showAlert('success', 'Refunded', res.message || 'Refund processed successfully.');
    } catch {
      showAlert('error', 'Error', 'Failed to process refund.');
    }
  };

  const inputCls = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';

  const returns = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div className="p-6">
      {alert && <div className="mb-4"><Alert variant={alert.variant} title={alert.title} message={alert.message} /></div>}

      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Returns & Refunds</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage customer return requests</p>
        </div>
      </div>

      {/* Filters */}
      <div className="mb-4 flex gap-3 flex-wrap">
        {['', 'requested', 'approved', 'rejected', 'received', 'refunded'].map((s) => (
          <button
            key={s || 'all'}
            onClick={() => { setStatusFilter(s); setPage(1); }}
            className={`px-3 py-1.5 text-sm rounded-lg border transition-colors ${
              statusFilter === s
                ? 'bg-brand-600 text-white border-brand-600'
                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-brand-400'
            }`}
          >
            {s ? s.charAt(0).toUpperCase() + s.slice(1) : 'All'}
          </button>
        ))}
      </div>

      {error && <Alert variant="error" title="Error" message="Failed to load returns." />}
      {isLoading && <div className="py-12 text-center text-gray-500">Loading...</div>}

      <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              {['Return #', 'Customer', 'Order', 'Reason', 'Status', 'Refund', 'Actions'].map((h) => (
                <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
            {returns.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-gray-400">No returns found.</td>
              </tr>
            ) : (
              returns.map((ret) => (
                <tr key={ret.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                  <td className="px-4 py-3 font-mono text-xs font-medium text-gray-900 dark:text-white">{ret.return_number}</td>
                  <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                    {ret.customer ? `${ret.customer.first_name} ${ret.customer.last_name}` : `#${ret.customer_id}`}
                  </td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-400">
                    {ret.order?.order_number ?? `#${ret.order_id}`}
                  </td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{REASON_LABELS[ret.reason] ?? ret.reason}</td>
                  <td className="px-4 py-3">
                    <Badge color={STATUS_BADGE[ret.status]}>{ret.status}</Badge>
                  </td>
                  <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                    {ret.refund_amount != null ? fmtCurrency(ret.refund_amount) : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex gap-1.5 flex-wrap">
                      {ret.status === 'requested' && (
                        <>
                          <Button size="sm" variant="outline" onClick={() => {
                            setSelected(ret);
                            setApproveForm({ refund_amount: ret.order?.total_amount ?? 0, admin_notes: '' });
                            setAction('approve');
                          }}>Approve</Button>
                          <Button size="sm" variant="outline" onClick={() => {
                            setSelected(ret);
                            setRejectNotes('');
                            setAction('reject');
                          }} className="text-red-600 border-red-300 hover:bg-red-50">Reject</Button>
                        </>
                      )}
                      {ret.status === 'approved' && (
                        <Button size="sm" variant="primary" onClick={() => handleRefund(ret)} disabled={isRefunding}>
                          Process Refund
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex justify-center gap-2 mt-4">
          <Button size="sm" variant="outline" disabled={page === 1} onClick={() => setPage(page - 1)}>Prev</Button>
          <span className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
            {page} / {meta.last_page}
          </span>
          <Button size="sm" variant="outline" disabled={page === meta.last_page} onClick={() => setPage(page + 1)}>Next</Button>
        </div>
      )}

      {/* Approve Modal */}
      <Modal isOpen={action === 'approve'} onClose={() => setAction(null)} className="max-w-sm w-full">
        <div className="p-6">
          <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Approve Return</h2>
          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Refund Amount ($)</label>
              <input type="number" min="0" step="0.01" className={inputCls}
                value={approveForm.refund_amount}
                onChange={(e) => setApproveForm({ ...approveForm, refund_amount: parseFloat(e.target.value) || 0 })} />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Admin Notes (optional)</label>
              <textarea className={`${inputCls} resize-none`} rows={3}
                value={approveForm.admin_notes}
                onChange={(e) => setApproveForm({ ...approveForm, admin_notes: e.target.value })} />
            </div>
          </div>
          <div className="flex gap-3 justify-end mt-6">
            <Button variant="outline" onClick={() => setAction(null)}>Cancel</Button>
            <Button variant="primary" onClick={handleApprove} disabled={isApproving}>Approve Return</Button>
          </div>
        </div>
      </Modal>

      {/* Reject Modal */}
      <Modal isOpen={action === 'reject'} onClose={() => setAction(null)} className="max-w-sm w-full">
        <div className="p-6">
          <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Reject Return</h2>
          <div>
            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Reason for rejection *</label>
            <textarea className={`${inputCls} resize-none`} rows={4}
              value={rejectNotes}
              onChange={(e) => setRejectNotes(e.target.value)}
              placeholder="Explain why this return is being rejected..." />
          </div>
          <div className="flex gap-3 justify-end mt-6">
            <Button variant="outline" onClick={() => setAction(null)}>Cancel</Button>
            <Button variant="primary" className="bg-red-600 hover:bg-red-700 border-red-600" onClick={handleReject} disabled={isRejecting}>
              Reject Return
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  );
}
