import type { IServiceOffering, IServiceProviderDetails } from 'src/types/marketplace';

import Box from '@mui/material/Box';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Avatar from '@mui/material/Avatar';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import Divider from '@mui/material/Divider';
import Tooltip from '@mui/material/Tooltip';
import IconButton from '@mui/material/IconButton';
import Typography from '@mui/material/Typography';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';

import { useTranslate } from 'src/locales';

import { Iconify } from 'src/components/iconify';

import { CATEGORY_ICONS, DELIVERY_MODE_OPTIONS } from 'src/types/marketplace';

// ----------------------------------------------------------------------

type Props = {
  open: boolean;
  onClose: () => void;
  provider: IServiceProviderDetails | null;
  loading?: boolean;
  onInquiry: (provider: IServiceProviderDetails, offering?: IServiceOffering) => void;
};

export function ProviderDetailDialog({ open, onClose, provider, loading, onInquiry }: Props) {
  const { t } = useTranslate('marketplace');
  if (!provider && !loading) return null;

  return (
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle sx={{ pb: 1 }}>
        <Stack direction="row" alignItems="center" spacing={2}>
          <Avatar
            src={provider?.logoUrl || undefined}
            alt={provider?.companyName}
            sx={{
              width: 64,
              height: 64,
              bgcolor: 'primary.lighter',
              color: 'primary.main',
              fontSize: '1.75rem',
              fontWeight: 'bold',
            }}
          >
            {provider?.companyName?.charAt(0).toUpperCase()}
          </Avatar>
          <Box sx={{ flex: 1 }}>
            <Typography variant="h5">{provider?.companyName}</Typography>
            <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap sx={{ mt: 0.5 }}>
              {provider?.isNationwide && (
                <Chip size="small" label={t('provider.card.nationwide')} color="info" variant="soft" />
              )}
              {provider?.offersRemote && (
                <Chip
                  size="small"
                  label={t('provider.card.remote')}
                  icon={<Iconify icon="solar:monitor-bold" width={14} />}
                  variant="outlined"
                />
              )}
              {provider?.website && (
                <Chip
                  size="small"
                  label={t('provider.detail.website')}
                  icon={<Iconify icon="solar:globe-bold" width={14} />}
                  component="a"
                  href={provider.website}
                  target="_blank"
                  rel="noopener noreferrer"
                  clickable
                  variant="outlined"
                />
              )}
            </Stack>
          </Box>
          <IconButton onClick={onClose}>
            <Iconify icon="solar:close-circle-bold" width={24} />
          </IconButton>
        </Stack>
      </DialogTitle>

      <DialogContent dividers>
        {loading ? (
          <Box sx={{ py: 4, textAlign: 'center' }}>
            <Typography color="text.secondary">{t('provider.detail.loading')}</Typography>
          </Box>
        ) : provider ? (
          <Stack spacing={3}>
            {/* Categories */}
            {provider.categories.length > 0 && (
              <Box>
                <Typography variant="subtitle2" gutterBottom>
                  {t('provider.detail.categories')}
                </Typography>
                <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                  {provider.categories.map((category) => (
                    <Chip
                      key={category.id}
                      label={category.name}
                      icon={
                        <Iconify
                          icon={CATEGORY_ICONS[category.slug] || 'solar:health-bold'}
                          width={16}
                        />
                      }
                      color="primary"
                      variant="soft"
                    />
                  ))}
                </Stack>
              </Box>
            )}

            {/* Tags */}
            {provider.tags.length > 0 && (
              <Box>
                <Typography variant="subtitle2" gutterBottom>
                  {t('provider.detail.tags')}
                </Typography>
                <Stack direction="row" spacing={0.5} flexWrap="wrap" useFlexGap>
                  {provider.tags.map((tag) => (
                    <Chip key={tag.id} label={tag.name} size="small" variant="outlined" />
                  ))}
                </Stack>
              </Box>
            )}

            {/* Description */}
            <Box>
              <Typography variant="subtitle2" gutterBottom>
                {t('provider.detail.about')}
              </Typography>
              <Typography variant="body2" color="text.secondary" sx={{ whiteSpace: 'pre-line' }}>
                {provider.description}
              </Typography>
            </Box>

            {/* Contact Info */}
            <Box>
              <Typography variant="subtitle2" gutterBottom>
                {t('provider.detail.contact')}
              </Typography>
              <Stack spacing={1}>
                {provider.contactPerson && (
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Iconify icon="solar:user-bold" width={18} color="text.secondary" />
                    <Typography variant="body2">{provider.contactPerson}</Typography>
                  </Stack>
                )}
                <Stack direction="row" spacing={1} alignItems="center">
                  <Iconify icon="solar:letter-bold" width={18} color="text.secondary" />
                  <Typography variant="body2">{provider.contactEmail}</Typography>
                </Stack>
                {provider.contactPhone && (
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Iconify icon="solar:phone-bold" width={18} color="text.secondary" />
                    <Typography variant="body2">{provider.contactPhone}</Typography>
                  </Stack>
                )}
                {provider.location && (
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Iconify icon="solar:map-point-bold" width={18} color="text.secondary" />
                    <Typography variant="body2">
                      {[provider.location.city, provider.location.region]
                        .filter(Boolean)
                        .join(', ')}
                    </Typography>
                  </Stack>
                )}
              </Stack>
            </Box>

            {/* Offerings */}
            {provider.offerings && provider.offerings.length > 0 && (
              <Box>
                <Divider sx={{ my: 1 }} />
                <Typography variant="subtitle2" gutterBottom sx={{ mt: 2 }}>
                  {t('provider.detail.offerings')} ({provider.offerings.length})
                </Typography>
                <Stack spacing={2}>
                  {provider.offerings.map((offering) => (
                    <OfferingCard
                      key={offering.id}
                      offering={offering}
                      onInquiry={() => onInquiry(provider, offering)}
                    />
                  ))}
                </Stack>
              </Box>
            )}
          </Stack>
        ) : null}
      </DialogContent>

      <DialogActions sx={{ px: 3, py: 2 }}>
        <Button onClick={onClose} color="inherit">
          {t('provider.detail.close')}
        </Button>
        {provider && (
          <Button
            variant="contained"
            startIcon={<Iconify icon="solar:letter-bold" width={18} />}
            onClick={() => onInquiry(provider)}
          >
            {t('provider.detail.inquiry')}
          </Button>
        )}
      </DialogActions>
    </Dialog>
  );
}

// ----------------------------------------------------------------------

type OfferingCardProps = {
  offering: IServiceOffering;
  onInquiry: () => void;
};

function OfferingCard({ offering, onInquiry }: OfferingCardProps) {
  const { t } = useTranslate('marketplace');
  const deliveryLabels = offering.deliveryModes
    .map((mode) => DELIVERY_MODE_OPTIONS.find((o) => o.value === mode)?.label || mode)
    .join(', ');

  return (
    <Box
      sx={{
        p: 2,
        border: 1,
        borderColor: 'divider',
        borderRadius: 1.5,
        bgcolor: 'background.neutral',
      }}
    >
      <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
        <Box sx={{ flex: 1 }}>
          <Stack direction="row" alignItems="center" spacing={1}>
            <Typography variant="subtitle2">{offering.title}</Typography>
            {offering.isCertified && (
              <Tooltip title={offering.certificationName || t('provider.detail.certified')}>
                <Iconify icon="solar:verified-check-bold" width={18} color="success.main" />
              </Tooltip>
            )}
          </Stack>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            {offering.description}
          </Typography>
          <Stack direction="row" spacing={1} sx={{ mt: 1 }} flexWrap="wrap" useFlexGap>
            <Chip
              size="small"
              label={deliveryLabels}
              icon={<Iconify icon="solar:map-bold" width={14} />}
              variant="outlined"
            />
            {offering.duration && (
              <Chip
                size="small"
                label={offering.duration}
                icon={<Iconify icon="solar:clock-circle-bold" width={14} />}
                variant="outlined"
              />
            )}
            {(offering.minParticipants || offering.maxParticipants) && (
              <Chip
                size="small"
                label={`${offering.minParticipants || 1}–${offering.maxParticipants || '∞'} ${t('provider.detail.participants')}`}
                icon={<Iconify icon="solar:users-group-rounded-bold" width={14} />}
                variant="outlined"
              />
            )}
          </Stack>
        </Box>
        <Button size="small" variant="soft" onClick={onInquiry} sx={{ ml: 2 }}>
          {t('provider.detail.ask')}
        </Button>
      </Stack>
    </Box>
  );
}

