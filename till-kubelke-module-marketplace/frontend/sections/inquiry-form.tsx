import type { IServiceProvider, IServiceOffering } from 'src/types/marketplace';

import { useState, useEffect } from 'react';

import Box from '@mui/material/Box';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import MenuItem from '@mui/material/MenuItem';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import LoadingButton from '@mui/lab/LoadingButton';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import CircularProgress from '@mui/material/CircularProgress';

import { useTranslate } from 'src/locales';
import { createInquiry, useGetProviderDetails } from 'src/actions/marketplace';

import { toast } from 'src/components/snackbar';
import { Iconify } from 'src/components/iconify';

// ----------------------------------------------------------------------

type Props = {
  open: boolean;
  onClose: () => void;
  provider: IServiceProvider | null;
  offering?: IServiceOffering | null;
  offerings?: IServiceOffering[];
  bgmProjectId?: number;
  defaultContactName?: string;
  defaultContactEmail?: string;
};

export function InquiryForm({
  open,
  onClose,
  provider,
  offering,
  offerings: propOfferings,
  bgmProjectId,
  defaultContactName = '',
  defaultContactEmail = '',
}: Props) {
  const { t } = useTranslate('marketplace');
  const [loading, setLoading] = useState(false);
  const [selectedOfferingId, setSelectedOfferingId] = useState<number | ''>(offering?.id || '');
  const [formData, setFormData] = useState({
    contactName: defaultContactName,
    contactEmail: defaultContactEmail,
    contactPhone: '',
    message: '',
  });
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Fetch provider details to get offerings if not provided
  const shouldFetchDetails = open && provider && !propOfferings && !offering;
  const { provider: providerDetails, providerLoading } = useGetProviderDetails(
    shouldFetchDetails ? provider?.id || null : null
  );

  // Get offerings from props or fetched details
  const availableOfferings = propOfferings || providerDetails?.offerings || [];

  // Update selected offering when dialog opens with a pre-selected offering
  useEffect(() => {
    if (open && offering?.id) {
      setSelectedOfferingId(offering.id);
    } else if (open && !offering) {
      setSelectedOfferingId('');
    }
  }, [open, offering]);

  const handleChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (errors[field]) {
      setErrors((prev) => {
        const newErrors = { ...prev };
        delete newErrors[field];
        return newErrors;
      });
    }
  };

  // Get the currently selected offering object
  const currentOffering = selectedOfferingId 
    ? availableOfferings.find(o => o.id === selectedOfferingId) || offering
    : offering;

  const validate = () => {
    const newErrors: Record<string, string> = {};

    if (!formData.contactName.trim()) {
      newErrors.contactName = t('inquiry.form.nameRequired');
    }
    if (!formData.contactEmail.trim()) {
      newErrors.contactEmail = t('inquiry.form.emailRequired');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.contactEmail)) {
      newErrors.contactEmail = t('inquiry.form.emailInvalid');
    }
    if (!formData.message.trim()) {
      newErrors.message = t('inquiry.form.messageRequired');
    } else if (formData.message.length < 20) {
      newErrors.message = t('inquiry.form.messageMinLength');
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async () => {
    if (!provider || !validate()) return;

    setLoading(true);
    try {
      await createInquiry({
        providerId: provider.id,
        offeringId: currentOffering?.id,
        bgmProjectId,
        contactName: formData.contactName,
        contactEmail: formData.contactEmail,
        contactPhone: formData.contactPhone || undefined,
        message: formData.message,
      });

      toast.success(t('inquiry.success'));
      onClose();
      
      // Reset form
      setFormData({
        contactName: defaultContactName,
        contactEmail: defaultContactEmail,
        contactPhone: '',
        message: '',
      });
      setSelectedOfferingId('');
    } catch (error: any) {
      const errorMessage = error?.response?.data?.error || error?.message || 'Fehler beim Senden der Anfrage.';
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  if (!provider) return null;

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth>
      <DialogTitle>
        <Stack direction="row" alignItems="center" spacing={1}>
          <Iconify icon="solar:letter-bold" width={24} color="primary.main" />
          <Box>
            <Typography variant="h6">{t('inquiry.title', { companyName: provider.companyName })}</Typography>
            {currentOffering && (
              <Typography variant="body2" color="text.secondary">
                {currentOffering.title}
              </Typography>
            )}
          </Box>
        </Stack>
      </DialogTitle>

      <DialogContent>
        <Stack spacing={2.5} sx={{ pt: 1 }}>
          {/* Offering Selection - show when offerings available and none pre-selected */}
          {availableOfferings.length > 0 && !offering && (
            <Box>
              {providerLoading ? (
                <Stack direction="row" spacing={1} alignItems="center" sx={{ py: 1 }}>
                  <CircularProgress size={16} />
                  <Typography variant="body2" color="text.secondary">
                    Angebote werden geladen...
                  </Typography>
                </Stack>
              ) : (
                <TextField
                  select
                  fullWidth
                  label="Angebot auswählen"
                  value={selectedOfferingId}
                  onChange={(e) => setSelectedOfferingId(e.target.value as number | '')}
                  helperText="Wählen Sie das Angebot, zu dem Sie anfragen möchten (optional)"
                >
                  <MenuItem value="">
                    <em>Allgemeine Anfrage</em>
                  </MenuItem>
                  {availableOfferings.map((off) => (
                    <MenuItem key={off.id} value={off.id}>
                      <Stack direction="row" alignItems="center" spacing={1} sx={{ width: '100%' }}>
                        <Box sx={{ flex: 1 }}>
                          <Typography variant="body2">{off.title}</Typography>
                          {off.isCertified && (
                            <Chip 
                              label="§20" 
                              size="small" 
                              color="success" 
                              variant="soft"
                              sx={{ height: 18, fontSize: '0.65rem', ml: 0.5 }}
                            />
                          )}
                        </Box>
                        {off.pricingInfo?.amount && (
                          <Typography variant="caption" color="text.secondary">
                            ab {off.pricingInfo.amount}€
                          </Typography>
                        )}
                      </Stack>
                    </MenuItem>
                  ))}
                </TextField>
              )}
            </Box>
          )}

          <TextField
            fullWidth
            label={t('inquiry.form.name')}
            value={formData.contactName}
            onChange={(e) => handleChange('contactName', e.target.value)}
            error={!!errors.contactName}
            helperText={errors.contactName}
            required
          />

          <TextField
            fullWidth
            label={t('inquiry.form.email')}
            type="email"
            value={formData.contactEmail}
            onChange={(e) => handleChange('contactEmail', e.target.value)}
            error={!!errors.contactEmail}
            helperText={errors.contactEmail}
            required
          />

          <TextField
            fullWidth
            label={t('inquiry.form.phone')}
            value={formData.contactPhone}
            onChange={(e) => handleChange('contactPhone', e.target.value)}
          />

          <TextField
            fullWidth
            label={t('inquiry.form.message')}
            multiline
            rows={4}
            value={formData.message}
            onChange={(e) => handleChange('message', e.target.value)}
            error={!!errors.message}
            helperText={errors.message}
            required
            placeholder={t('inquiry.form.messagePlaceholder')}
          />

          <Typography variant="caption" color="text.secondary">
            Ihre Kontaktdaten werden an den Dienstleister weitergeleitet, damit dieser sich mit Ihnen in Verbindung setzen kann.
          </Typography>
        </Stack>
      </DialogContent>

      <DialogActions sx={{ px: 3, py: 2 }}>
        <Button onClick={onClose} color="inherit" disabled={loading}>
          {t('inquiry.form.cancel')}
        </Button>
        <LoadingButton
          variant="contained"
          loading={loading}
          onClick={handleSubmit}
          startIcon={<Iconify icon="solar:paper-plane-bold" width={18} />}
        >
          {t('inquiry.form.submit')}
        </LoadingButton>
      </DialogActions>
    </Dialog>
  );
}

