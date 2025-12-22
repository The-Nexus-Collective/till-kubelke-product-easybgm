import type { IServiceProvider, IServiceOffering, IServiceProviderDetails } from 'src/types/marketplace';

import { useState, useCallback } from 'react';
import { useBoolean } from 'minimal-shared/hooks';

import Box from '@mui/material/Box';
import Card from '@mui/material/Card';
import Chip from '@mui/material/Chip';
import Stack from '@mui/material/Stack';
import Button from '@mui/material/Button';
import Avatar from '@mui/material/Avatar';
import Tooltip from '@mui/material/Tooltip';
import Skeleton from '@mui/material/Skeleton';
import Typography from '@mui/material/Typography';
import CardHeader from '@mui/material/CardHeader';
import CardContent from '@mui/material/CardContent';

import { paths } from 'src/routes/paths';
import { RouterLink } from 'src/routes/components';

import { useTranslate } from 'src/locales';
import { useGetProviderDetails, useGetMarketplaceSuggestions } from 'src/actions/marketplace';

import { Iconify } from 'src/components/iconify';

import { CATEGORY_ICONS } from 'src/types/marketplace';

import { InquiryForm } from './inquiry-form';
import { ProviderDetailDialog } from './provider-detail-dialog';

// ----------------------------------------------------------------------

type Props = {
  phase?: number;
  goalTags?: string[];
  bgmProjectId?: number;
  limit?: number;
};

/**
 * Component that displays contextual BGM service provider suggestions
 * based on the current BGM phase and active goals.
 * 
 * Integrate this into BGM phase views to show relevant providers.
 */
export function MarketplaceSuggestions({ phase, goalTags, bgmProjectId, limit = 4 }: Props) {
  const { t } = useTranslate('marketplace');
  const { suggestions, suggestionsLoading, context } = useGetMarketplaceSuggestions({
    phase,
    goalTags,
    limit,
  });

  // Dialogs
  const detailDialog = useBoolean();
  const inquiryDialog = useBoolean();
  const [selectedProvider, setSelectedProvider] = useState<IServiceProvider | null>(null);
  const [selectedOffering, setSelectedOffering] = useState<IServiceOffering | null>(null);

  // Provider details for dialog
  const { provider: providerDetails, providerLoading } = useGetProviderDetails(
    detailDialog.value ? selectedProvider?.id || null : null
  );

  const handleViewDetails = useCallback((provider: IServiceProvider) => {
    setSelectedProvider(provider);
    detailDialog.onTrue();
  }, [detailDialog]);

  const handleInquiry = useCallback((provider: IServiceProvider) => {
    setSelectedProvider(provider);
    setSelectedOffering(null);
    detailDialog.onFalse();
    inquiryDialog.onTrue();
  }, [detailDialog, inquiryDialog]);

  const handleInquiryFromDetail = useCallback((provider: IServiceProviderDetails, offering?: IServiceOffering) => {
    setSelectedProvider(provider);
    setSelectedOffering(offering || null);
    detailDialog.onFalse();
    inquiryDialog.onTrue();
  }, [detailDialog, inquiryDialog]);

  if (suggestionsLoading) {
    return (
      <Card>
        <CardHeader
          title={<Skeleton width={200} />}
          subheader={<Skeleton width={150} />}
        />
        <CardContent>
          <Stack spacing={2}>
            {[1, 2, 3].map((i) => (
              <Skeleton key={i} variant="rounded" height={80} />
            ))}
          </Stack>
        </CardContent>
      </Card>
    );
  }

  if (!suggestions.length) {
    return null;
  }

  const phaseLabels: Record<number, string> = {
    1: 'Vorbereitung',
    2: 'Analyse',
    3: 'Planung',
    4: 'Maßnahmen',
    5: 'Evaluation',
    6: 'Nachhaltigkeit',
  };

  return (
    <>
      <Card>
        <CardHeader
          title={t('suggestions.title')}
          subheader={
            phase
              ? t('suggestions.subtitle')
              : t('suggestions.subtitle')
          }
          action={
            <Button
              component={RouterLink}
              href={paths.dashboard.marketplace?.root || '#'}
              size="small"
              endIcon={<Iconify icon="solar:arrow-right-linear" width={16} />}
            >
              {t('suggestions.viewAll')}
            </Button>
          }
        />
        <CardContent>
          <Stack spacing={2}>
            {suggestions.map((provider) => (
              <SuggestionCard
                key={provider.id}
                provider={provider}
                onViewDetails={() => handleViewDetails(provider)}
                onInquiry={() => handleInquiry(provider)}
              />
            ))}
          </Stack>

          {/* Context Tags */}
          {context && (context.categorySlugs.length > 0 || context.tagSlugs.length > 0) && (
            <Box sx={{ mt: 2, pt: 2, borderTop: 1, borderColor: 'divider' }}>
              <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 1 }}>
                Gesucht nach:
              </Typography>
              <Stack direction="row" spacing={0.5} flexWrap="wrap" useFlexGap>
                {context.tagSlugs.slice(0, 5).map((slug) => (
                  <Chip key={slug} label={slug.replace(/-/g, ' ')} size="small" variant="outlined" />
                ))}
              </Stack>
            </Box>
          )}
        </CardContent>
      </Card>

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
        bgmProjectId={bgmProjectId}
      />
    </>
  );
}

// ----------------------------------------------------------------------

type SuggestionCardProps = {
  provider: IServiceProvider;
  onViewDetails: () => void;
  onInquiry: () => void;
};

function SuggestionCard({ provider, onViewDetails, onInquiry }: SuggestionCardProps) {
  return (
    <Box
      sx={{
        p: 2,
        border: 1,
        borderColor: 'divider',
        borderRadius: 1.5,
        transition: 'all 0.2s',
        '&:hover': {
          borderColor: 'primary.main',
          bgcolor: 'action.hover',
        },
      }}
    >
      <Stack direction="row" spacing={2} alignItems="flex-start">
        <Avatar
          src={provider.logoUrl || undefined}
          alt={provider.companyName}
          sx={{
            width: 48,
            height: 48,
            bgcolor: 'primary.lighter',
            color: 'primary.main',
            fontSize: '1.25rem',
            fontWeight: 'bold',
          }}
        >
          {provider.companyName.charAt(0).toUpperCase()}
        </Avatar>

        <Box sx={{ flex: 1, minWidth: 0 }}>
          <Stack direction="row" alignItems="center" spacing={1}>
            <Typography variant="subtitle2" noWrap>
              {provider.companyName}
            </Typography>
            {provider.isNationwide && (
              <Chip label="Bundesweit" size="small" color="info" variant="soft" sx={{ height: 18, fontSize: '0.65rem' }} />
            )}
          </Stack>

          {provider.shortDescription && (
            <Typography
              variant="caption"
              color="text.secondary"
              sx={{
                display: '-webkit-box',
                WebkitLineClamp: 2,
                WebkitBoxOrient: 'vertical',
                overflow: 'hidden',
              }}
            >
              {provider.shortDescription}
            </Typography>
          )}

          <Stack direction="row" spacing={0.5} sx={{ mt: 1 }} flexWrap="wrap" useFlexGap>
            {provider.categories.slice(0, 2).map((category) => (
              <Tooltip key={category.id} title={category.name}>
                <Chip
                  size="small"
                  icon={
                    <Iconify
                      icon={CATEGORY_ICONS[category.slug] || 'solar:health-bold'}
                      width={12}
                    />
                  }
                  label={category.name}
                  sx={{ height: 20, fontSize: '0.65rem' }}
                />
              </Tooltip>
            ))}
          </Stack>
        </Box>

        <Stack spacing={0.5}>
          <Button size="small" variant="outlined" onClick={onViewDetails}>
            Details
          </Button>
          <Button size="small" variant="soft" color="primary" onClick={onInquiry}>
            Anfragen
          </Button>
        </Stack>
      </Stack>
    </Box>
  );
}

