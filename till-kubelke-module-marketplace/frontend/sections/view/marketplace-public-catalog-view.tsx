import type { IServiceProvider, IServiceOffering, IServiceProviderDetails } from 'src/types/marketplace';

import { useState, useCallback } from 'react';
import { useBoolean } from 'minimal-shared/hooks';

import Box from '@mui/material/Box';
import Card from '@mui/material/Card';
import Grid from '@mui/material/Grid';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Button from '@mui/material/Button';
import Switch from '@mui/material/Switch';
import Container from '@mui/material/Container';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import InputAdornment from '@mui/material/InputAdornment';
import TablePagination from '@mui/material/TablePagination';
import FormControlLabel from '@mui/material/FormControlLabel';
import CircularProgress from '@mui/material/CircularProgress';

import { paths } from 'src/routes/paths';
import { RouterLink } from 'src/routes/components';

import { useTranslate } from 'src/locales';
import { gradients, brandColors } from 'src/theme/gradients';
import { useGetProviderDetails, useGetMarketplaceCatalog, useGetMarketplaceCategories } from 'src/actions/marketplace';

import { Iconify } from 'src/components/iconify';

import { CATEGORY_ICONS } from 'src/types/marketplace';

import { InquiryForm } from '../inquiry-form';
import { ProviderCard } from '../provider-card';
import { ProviderDetailDialog } from '../provider-detail-dialog';

// ----------------------------------------------------------------------

export function MarketplacePublicCatalogView() {
  const { t } = useTranslate('marketplace');
  // Filters
  const [search, setSearch] = useState('');
  const [selectedCategories, setSelectedCategories] = useState<number[]>([]);
  const [nationwide, setNationwide] = useState(false);
  const [remote, setRemote] = useState(false);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(12);

  // Dialogs
  const detailDialog = useBoolean();
  const inquiryDialog = useBoolean();
  const [selectedProvider, setSelectedProvider] = useState<IServiceProvider | null>(null);
  const [selectedOffering, setSelectedOffering] = useState<IServiceOffering | null>(null);

  // Data
  const { categories } = useGetMarketplaceCategories();
  const { providers, pagination, catalogLoading, catalogEmpty } = useGetMarketplaceCatalog({
    categories: selectedCategories,
    search: search || undefined,
    nationwide,
    remote,
    page: page + 1,
    limit: rowsPerPage,
  });

  // Provider details for dialog
  const { provider: providerDetails, providerLoading } = useGetProviderDetails(
    detailDialog.value ? selectedProvider?.id || null : null
  );

  // Handlers
  const handleCategoryToggle = useCallback((categoryId: number) => {
    setSelectedCategories((prev) =>
      prev.includes(categoryId)
        ? prev.filter((id) => id !== categoryId)
        : [...prev, categoryId]
    );
    setPage(0);
  }, []);

  const handleClearFilters = useCallback(() => {
    setSearch('');
    setSelectedCategories([]);
    setNationwide(false);
    setRemote(false);
    setPage(0);
  }, []);

  const handleViewDetails = useCallback((provider: IServiceProvider) => {
    setSelectedProvider(provider);
    detailDialog.onTrue();
  }, [detailDialog]);

  const handleInquiry = useCallback((provider: IServiceProvider, offering?: IServiceOffering) => {
    setSelectedProvider(provider);
    setSelectedOffering(offering || null);
    detailDialog.onFalse();
    inquiryDialog.onTrue();
  }, [detailDialog, inquiryDialog]);

  const handleInquiryFromDetail = useCallback((provider: IServiceProviderDetails, offering?: IServiceOffering) => {
    setSelectedProvider(provider);
    setSelectedOffering(offering || null);
    detailDialog.onFalse();
    inquiryDialog.onTrue();
  }, [detailDialog, inquiryDialog]);

  const handleChangePage = useCallback((_: unknown, newPage: number) => {
    setPage(newPage);
  }, []);

  const handleChangeRowsPerPage = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  }, []);

  const isFiltered = search !== '' || selectedCategories.length > 0 || nationwide || remote;

  return (
    <>
      {/* Hero Section */}
      <Box
        component="section"
        sx={(theme) => ({
          py: { xs: 8, md: 12 },
          position: 'relative',
          overflow: 'hidden',
          background:
            theme.palette.mode === 'dark'
              ? gradients.marketplace.dark
              : gradients.marketplace.light,
        })}
      >
        {/* Blur elements */}
        <Box
          sx={{
            position: 'absolute',
            top: '-20%',
            left: '-10%',
            width: 400,
            height: 400,
            borderRadius: '50%',
            background: 'rgba(255, 255, 255, 0.1)',
            filter: 'blur(80px)',
            pointerEvents: 'none',
          }}
        />
        <Box
          sx={{
            position: 'absolute',
            bottom: '-10%',
            right: '-5%',
            width: 300,
            height: 300,
            borderRadius: '50%',
            background: 'rgba(255, 255, 255, 0.08)',
            filter: 'blur(60px)',
            pointerEvents: 'none',
          }}
        />

        <Container maxWidth="lg" sx={{ position: 'relative', zIndex: 2 }}>
          <Typography
            variant="h2"
            component="h1"
            sx={{
              mb: 2,
              textAlign: 'center',
              fontWeight: 800,
              color: 'common.white',
              textShadow: '0 2px 20px rgba(0,0,0,0.2)',
            }}
          >
            {t('catalog.title')}
          </Typography>
          <Typography
            variant="h5"
            sx={{
              mb: 4,
              textAlign: 'center',
              maxWidth: 700,
              mx: 'auto',
              color: 'rgba(255,255,255,0.9)',
              fontWeight: 400,
            }}
          >
            Finden Sie qualifizierte Dienstleister für Bewegung, Ernährung, Mentale Gesundheit, 
            Ergonomie und mehr – direkt für Ihr Betriebliches Gesundheitsmanagement.
          </Typography>

          {/* Stats Cards */}
          <Stack direction="row" spacing={2} justifyContent="center" flexWrap="wrap" useFlexGap>
            <Card
              sx={{
                py: 2,
                px: 3,
                minWidth: 140,
                textAlign: 'center',
                bgcolor: 'rgba(255,255,255,0.15)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255,255,255,0.2)',
              }}
            >
              <Typography variant="h4" sx={{ color: 'common.white' }}>
                {pagination.total || '100+'}
              </Typography>
              <Typography variant="body2" sx={{ color: 'rgba(255,255,255,0.8)' }}>
                {t('catalog.stats.providers')}
              </Typography>
            </Card>
            <Card
              sx={{
                py: 2,
                px: 3,
                minWidth: 140,
                textAlign: 'center',
                bgcolor: 'rgba(255,255,255,0.15)',
                backdropFilter: 'blur(10px)',
                border: '1px solid rgba(255,255,255,0.2)',
              }}
            >
              <Typography variant="h4" sx={{ color: 'common.white' }}>
                {categories.length || 5}
              </Typography>
              <Typography variant="body2" sx={{ color: 'rgba(255,255,255,0.8)' }}>
                {t('catalog.stats.categories')}
              </Typography>
            </Card>
          </Stack>

          {/* CTA for Providers */}
          <Box sx={{ mt: 4, textAlign: 'center' }}>
            <Button
              component={RouterLink}
              href={paths.marketplace.register}
              variant="contained"
              size="large"
              startIcon={<Iconify icon="solar:shop-bold" />}
              sx={{
                px: 4,
                py: 1.5,
                bgcolor: 'common.white',
                color: brandColors.primary,
                fontWeight: 700,
                boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
                '&:hover': {
                  bgcolor: 'rgba(255,255,255,0.9)',
                  transform: 'translateY(-2px)',
                  boxShadow: '0 12px 40px rgba(0,0,0,0.25)',
                },
                transition: 'all 0.3s ease',
              }}
            >
              {t('registration.form.submit')}
            </Button>
          </Box>
        </Container>
      </Box>

      {/* Catalog Section */}
      <Box component="section" sx={{ py: { xs: 4, md: 6 } }}>
        <Container maxWidth="lg">
          {/* Filters */}
          <Card sx={{ mb: 3 }}>
            <Stack spacing={2.5} sx={{ p: 2.5 }}>
              {/* Search and Toggles */}
              <Stack
                direction={{ xs: 'column', md: 'row' }}
                spacing={2}
                alignItems={{ xs: 'stretch', md: 'center' }}
              >
                <TextField
                  fullWidth
                  placeholder={t('catalog.search')}
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value);
                    setPage(0);
                  }}
                  InputProps={{
                    startAdornment: (
                      <InputAdornment position="start">
                        <Iconify icon="eva:search-fill" sx={{ color: 'text.disabled' }} />
                      </InputAdornment>
                    ),
                  }}
                  sx={{ maxWidth: { md: 320 } }}
                />

                <FormControlLabel
                  control={
                    <Switch
                      checked={nationwide}
                      onChange={(e) => {
                        setNationwide(e.target.checked);
                        setPage(0);
                      }}
                    />
                  }
                  label={t('catalog.filters.nationwide')}
                />

                <FormControlLabel
                  control={
                    <Switch
                      checked={remote}
                      onChange={(e) => {
                        setRemote(e.target.checked);
                        setPage(0);
                      }}
                    />
                  }
                  label={t('catalog.filters.remote')}
                />

                <Box sx={{ flexGrow: 1 }} />

                {isFiltered && (
                  <Button
                    color="error"
                    startIcon={<Iconify icon="solar:trash-bin-trash-bold" />}
                    onClick={handleClearFilters}
                  >
                    {t('catalog.resetFilters')}
                  </Button>
                )}
              </Stack>

              {/* Category Chips */}
              <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                {categories.map((category) => {
                  const isSelected = selectedCategories.includes(category.id);
                  return (
                    <Chip
                      key={category.id}
                      label={category.name}
                      icon={
                        <Iconify
                          icon={CATEGORY_ICONS[category.slug] || 'solar:health-bold'}
                          width={18}
                        />
                      }
                      color={isSelected ? 'primary' : 'default'}
                      variant={isSelected ? 'filled' : 'outlined'}
                      onClick={() => handleCategoryToggle(category.id)}
                      sx={{
                        cursor: 'pointer',
                        transition: 'all 0.2s',
                        '&:hover': {
                          transform: 'scale(1.05)',
                        },
                      }}
                    />
                  );
                })}
              </Stack>

              {/* Active Filters */}
              {isFiltered && (
                <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap>
                  {search && (
                    <Chip
                      label={`Suche: ${search}`}
                      size="small"
                      onDelete={() => setSearch('')}
                    />
                  )}
                  {selectedCategories.map((categoryId) => {
                    const category = categories.find((c) => c.id === categoryId);
                    return category ? (
                      <Chip
                        key={categoryId}
                        label={category.name}
                        size="small"
                        color="primary"
                        variant="soft"
                        onDelete={() => handleCategoryToggle(categoryId)}
                      />
                    ) : null;
                  })}
                  {nationwide && (
                    <Chip
                      label={t('catalog.filters.nationwide')}
                      size="small"
                      color="info"
                      variant="soft"
                      onDelete={() => setNationwide(false)}
                    />
                  )}
                  {remote && (
                    <Chip
                      label={t('catalog.filters.remote')}
                      size="small"
                      color="secondary"
                      variant="soft"
                      onDelete={() => setRemote(false)}
                    />
                  )}
                </Stack>
              )}
            </Stack>
          </Card>

          {/* Results */}
          {catalogLoading ? (
            <Box sx={{ py: 8, textAlign: 'center' }}>
              <CircularProgress />
              <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
                {t('catalog.loading')}
              </Typography>
            </Box>
          ) : catalogEmpty ? (
            <Box sx={{ py: 8, textAlign: 'center' }}>
              <Iconify icon="solar:box-minimalistic-bold" width={64} sx={{ color: 'text.disabled', mb: 2 }} />
              <Typography variant="h6" color="text.secondary">
                {t('catalog.empty.title')}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                {t('catalog.empty.description')}
              </Typography>
              {isFiltered && (
                <Button
                  sx={{ mt: 2 }}
                  onClick={handleClearFilters}
                  startIcon={<Iconify icon="solar:refresh-bold" />}
                >
                  {t('catalog.resetFilters')}
                </Button>
              )}
            </Box>
          ) : (
            <>
              <Grid container spacing={3}>
                {providers.map((provider) => (
                  <Grid key={provider.id} size={{ xs: 12, sm: 6, md: 4, lg: 3 }}>
                    <ProviderCard
                      provider={provider}
                      onViewDetails={handleViewDetails}
                      onInquiry={handleInquiry}
                    />
                  </Grid>
                ))}
              </Grid>

              <TablePagination
                component="div"
                count={pagination.total}
                page={page}
                rowsPerPage={rowsPerPage}
                onPageChange={handleChangePage}
                onRowsPerPageChange={handleChangeRowsPerPage}
                rowsPerPageOptions={[12, 24, 48]}
                labelRowsPerPage="Pro Seite:"
                labelDisplayedRows={({ from, to, count }) => `${from}–${to} von ${count}`}
                sx={{ mt: 3 }}
              />
            </>
          )}
        </Container>
      </Box>

      {/* Provider Detail Dialog */}
      <ProviderDetailDialog
        open={detailDialog.value}
        onClose={detailDialog.onFalse}
        provider={providerDetails}
        loading={providerLoading}
        onInquiry={handleInquiryFromDetail}
      />

      {/* Inquiry Form Dialog */}
      <InquiryForm
        open={inquiryDialog.value}
        onClose={inquiryDialog.onFalse}
        provider={selectedProvider}
        offering={selectedOffering}
      />
    </>
  );
}

