

import Box from '@mui/material/Box';
import Card from '@mui/material/Card';
import Grid from '@mui/material/Grid';
import Stack from '@mui/material/Stack';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Container from '@mui/material/Container';
import Typography from '@mui/material/Typography';
import CardContent from '@mui/material/CardContent';
import CircularProgress from '@mui/material/CircularProgress';

import { paths } from 'src/routes/paths';
import { RouterLink } from 'src/routes/components';

import { useTranslate } from 'src/locales';
import { useGetMyProvider } from 'src/actions/marketplace';

import { Label } from 'src/components/label';
import { Iconify } from 'src/components/iconify';

import { ProviderRegistrationForm } from '../provider-registration-form';

// ----------------------------------------------------------------------

// ----------------------------------------------------------------------

export function ProviderManagementView() {
  const { t } = useTranslate('marketplace');
  const { hasProvider, provider, myProviderLoading, myProviderError } = useGetMyProvider();

  const STATUS_CONFIG: Record<string, { label: string; color: 'default' | 'warning' | 'success' | 'error' }> = {
    pending: { label: t('provider.management.status.pending'), color: 'warning' },
    approved: { label: t('provider.management.status.approved'), color: 'success' },
    rejected: { label: t('provider.management.status.rejected'), color: 'error' },
  };

  if (myProviderLoading) {
    return (
      <Container maxWidth="lg" sx={{ py: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: 400 }}>
          <CircularProgress />
        </Box>
      </Container>
    );
  }

  if (myProviderError) {
    return (
      <Container maxWidth="lg" sx={{ py: 4 }}>
        <Alert severity="error">
          {t('provider.management.error')}
        </Alert>
      </Container>
    );
  }

  // No provider yet - show registration form
  if (!hasProvider || !provider) {
    return (
      <Container maxWidth="lg" sx={{ py: 4 }}>
        <Stack spacing={4}>
          <Box>
            <Typography variant="h4" sx={{ mb: 1 }}>
              {t('provider.management.title')}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {t('provider.management.description')}
            </Typography>
          </Box>

          <Card>
            <CardContent>
              <ProviderRegistrationForm />
            </CardContent>
          </Card>
        </Stack>
      </Container>
    );
  }

  // Provider exists - show management view
  const statusConfig = STATUS_CONFIG[provider.status] || STATUS_CONFIG.pending;

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      <Stack spacing={4}>
        {/* Header */}
        <Box>
          <Stack direction="row" spacing={2} alignItems="center" sx={{ mb: 2 }}>
            <Typography variant="h4">
              {provider.companyName}
            </Typography>
            <Label color={statusConfig.color} variant="filled">
              {statusConfig.label}
            </Label>
          </Stack>
          <Typography variant="body2" color="text.secondary">
            {t('provider.management.manageTitle')}
          </Typography>
        </Box>

        {/* Status Alert */}
        {provider.status === 'pending' && (
          <Alert severity="warning" icon={<Iconify icon="solar:clock-circle-bold" />}>
            Ihr Profil wartet auf Freigabe durch einen Administrator. Sie erhalten eine E-Mail, sobald Ihr Profil freigeschaltet wurde.
          </Alert>
        )}

        {provider.status === 'rejected' && provider.rejectionReason && (
          <Alert severity="error" icon={<Iconify icon="solar:danger-triangle-bold" />}>
            <Typography variant="subtitle2" sx={{ mb: 0.5 }}>
              Ihr Profil wurde abgelehnt
            </Typography>
            <Typography variant="body2">
              Grund: {provider.rejectionReason}
            </Typography>
          </Alert>
        )}

        {/* Provider Info Cards */}
        <Grid container spacing={3}>
          {/* Company Info */}
          <Grid size={{ xs: 12, md: 6 }}>
            <Card>
              <CardContent>
                <Stack spacing={2}>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Iconify icon="solar:buildings-bold" width={24} />
                    <Typography variant="h6">Unternehmensdaten</Typography>
                  </Box>
                  
                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      Firmenname
                    </Typography>
                    <Typography variant="body1">{provider.companyName}</Typography>
                  </Box>

                  {provider.contactPerson && (
                    <Box>
                      <Typography variant="caption" color="text.secondary">
                        Ansprechpartner
                      </Typography>
                      <Typography variant="body1">{provider.contactPerson}</Typography>
                    </Box>
                  )}

                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      E-Mail
                    </Typography>
                    <Typography variant="body1">{provider.contactEmail}</Typography>
                  </Box>

                  {provider.contactPhone && (
                    <Box>
                      <Typography variant="caption" color="text.secondary">
                        Telefon
                      </Typography>
                      <Typography variant="body1">{provider.contactPhone}</Typography>
                    </Box>
                  )}

                  {provider.website && (
                    <Box>
                      <Typography variant="caption" color="text.secondary">
                        Website
                      </Typography>
                      <Typography 
                        variant="body1" 
                        component="a" 
                        href={provider.website} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        sx={{ color: 'primary.main', textDecoration: 'none' }}
                      >
                        {provider.website}
                      </Typography>
                    </Box>
                  )}
                </Stack>
              </CardContent>
            </Card>
          </Grid>

          {/* Service Info */}
          <Grid size={{ xs: 12, md: 6 }}>
            <Card>
              <CardContent>
                <Stack spacing={2}>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Iconify icon="solar:shop-bold" width={24} />
                    <Typography variant="h6">Service-Informationen</Typography>
                  </Box>

                  <Box>
                    <Typography variant="caption" color="text.secondary">
                      Beschreibung
                    </Typography>
                    <Typography variant="body2" sx={{ mt: 0.5 }}>
                      {provider.description}
                    </Typography>
                  </Box>

                  {provider.shortDescription && (
                    <Box>
                      <Typography variant="caption" color="text.secondary">
                        Kurzbeschreibung
                      </Typography>
                      <Typography variant="body2" sx={{ mt: 0.5 }}>
                        {provider.shortDescription}
                      </Typography>
                    </Box>
                  )}

                  <Stack direction="row" spacing={2}>
                    {provider.isNationwide && (
                      <Label color="info" variant="soft">
                        <Iconify icon="solar:global-bold" width={16} sx={{ mr: 0.5 }} />
                        Bundesweit
                      </Label>
                    )}
                    {provider.offersRemote && (
                      <Label color="success" variant="soft">
                        <Iconify icon="solar:laptop-bold" width={16} sx={{ mr: 0.5 }} />
                        Remote
                      </Label>
                    )}
                  </Stack>

                  {provider.categories && provider.categories.length > 0 && (
                    <Box>
                      <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 1 }}>
                        Kategorien
                      </Typography>
                      <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                        {provider.categories.map((cat) => (
                          <Label key={cat.id} variant="soft">
                            {cat.name}
                          </Label>
                        ))}
                      </Stack>
                    </Box>
                  )}
                </Stack>
              </CardContent>
            </Card>
          </Grid>

          {/* Offerings */}
          {provider.offerings && provider.offerings.length > 0 && (
            <Grid size={{ xs: 12 }}>
              <Card>
                <CardContent>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
                    <Iconify icon="solar:list-check-bold" width={24} />
                    <Typography variant="h6">Angebote</Typography>
                  </Box>
                  <Stack spacing={2}>
                    {provider.offerings.map((offering) => (
                      <Box key={offering.id} sx={{ p: 2, bgcolor: 'background.neutral', borderRadius: 1 }}>
                        <Typography variant="subtitle1">{offering.title}</Typography>
                        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
                          {offering.description}
                        </Typography>
                      </Box>
                    ))}
                  </Stack>
                </CardContent>
              </Card>
            </Grid>
          )}
        </Grid>

        {/* Actions */}
        <Stack direction="row" spacing={2}>
          <Button
            variant="outlined"
            component={RouterLink}
            href={paths.marketplace.provider(provider.id)}
            startIcon={<Iconify icon="solar:eye-bold" />}
          >
            Profil im Marktplatz ansehen
          </Button>
          <Button
            variant="outlined"
            component={RouterLink}
            href={paths.marketplace.register}
            startIcon={<Iconify icon="solar:pen-bold" />}
          >
            Profil bearbeiten
          </Button>
        </Stack>
      </Stack>
    </Container>
  );
}

