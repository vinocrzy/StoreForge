import type { ProfileUser } from '../../services/profile';
import { useAppSelector } from '../../store/hooks';

interface UserMetaCardProps {
  profile: ProfileUser;
}

export default function UserMetaCard({ profile }: UserMetaCardProps) {
  const currentStore = useAppSelector((state) => state.auth.currentStore);
  const initials = profile.name
    .split(' ')
    .map((word) => word[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  const primaryRole = profile.roles?.[0] ?? 'Staff';

  return (
    <div className="p-5 border border-gray-200 rounded-2xl dark:border-gray-800 lg:p-6">
      <div className="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div className="flex flex-col items-center w-full gap-6 xl:flex-row">
          <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-gray-100 text-xl font-semibold text-gray-700 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200">
            {profile.avatar_url ? (
              <img src={profile.avatar_url} alt={profile.name} className="h-full w-full object-cover" />
            ) : (
              initials
            )}
          </div>
          <div className="order-3 xl:order-2">
            <h4 className="mb-2 text-lg font-semibold text-center text-gray-800 dark:text-white/90 xl:text-left">
              {profile.name}
            </h4>
            <div className="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
              <p className="text-sm text-gray-500 dark:text-gray-400">
                {primaryRole}
              </p>
              <div className="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block"></div>
              <p className="text-sm text-gray-500 dark:text-gray-400">
                {currentStore?.name ?? 'Current Store'}
              </p>
            </div>
          </div>
          <div className="flex items-center order-2 gap-2 grow xl:order-3 xl:justify-end">
            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${
              profile.status === 'active'
                ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400'
                : 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400'
            }`}>
              {profile.status}
            </span>
            <span className="inline-flex items-center rounded-full bg-blue-light-50 px-3 py-1 text-xs font-medium text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400">
              {profile.roles?.join(', ') || 'No role'}
            </span>
          </div>
        </div>

        <div className="w-full lg:w-auto">
          <div className="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.02]">
            <p className="text-xs text-gray-500 dark:text-gray-400">Email</p>
            <p className="text-sm font-medium text-gray-800 dark:text-white/90">{profile.email}</p>
            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Phone</p>
            <p className="text-sm font-medium text-gray-800 dark:text-white/90">{profile.phone}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
