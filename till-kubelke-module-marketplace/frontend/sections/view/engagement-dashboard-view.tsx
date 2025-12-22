import type { IPartnerEngagement } from "src/types/marketplace";

import { useState, useCallback } from "react";

import Box from "@mui/material/Box";
import Tab from "@mui/material/Tab";
import Tabs from "@mui/material/Tabs";
import Card from "@mui/material/Card";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Skeleton from "@mui/material/Skeleton";
import Container from "@mui/material/Container";
import Typography from "@mui/material/Typography";
import CardContent from "@mui/material/CardContent";

import { paths } from "src/routes/paths";
import { RouterLink } from "src/routes/components";

import { useTranslate } from "src/locales";
import {
  grantDataScopes,
  cancelEngagement,
  useGetEngagements,
  activateEngagement,
  completeEngagement,
} from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";
import { EmptyContent } from "src/components/empty-content";
import { CustomBreadcrumbs } from "src/components/custom-breadcrumbs";

import { EngagementCard } from "../engagement-card";

// ----------------------------------------------------------------------

const STATUS_TABS = [
  { value: "all", label: "Alle", icon: "solar:list-bold" },
  { value: "active", label: "Aktiv", icon: "solar:play-bold" },
  { value: "data_shared", label: "Daten geteilt", icon: "solar:share-bold" },
  { value: "processing", label: "In Bearbeitung", icon: "solar:refresh-bold" },
  { value: "delivered", label: "Geliefert", icon: "solar:inbox-in-bold" },
  {
    value: "completed",
    label: "Abgeschlossen",
    icon: "solar:check-circle-bold",
  },
];

export function EngagementDashboardView() {
  const { t } = useTranslate("marketplace");
  const [currentTab, setCurrentTab] = useState("all");

  const { engagements, engagementsLoading, engagementsMutate } =
    useGetEngagements();

  const handleChangeTab = useCallback(
    (event: React.SyntheticEvent, newValue: string) => {
      setCurrentTab(newValue);
    },
    [],
  );

  const handleActivate = useCallback(
    async (engagement: IPartnerEngagement) => {
      await activateEngagement(engagement.id);
      engagementsMutate();
    },
    [engagementsMutate],
  );

  const handleCancel = useCallback(
    async (engagement: IPartnerEngagement) => {
      await cancelEngagement(engagement.id);
      engagementsMutate();
    },
    [engagementsMutate],
  );

  const handleComplete = useCallback(
    async (engagement: IPartnerEngagement) => {
      await completeEngagement(engagement.id);
      engagementsMutate();
    },
    [engagementsMutate],
  );

  const handleGrantData = useCallback(
    async (engagement: IPartnerEngagement, scopes: string[]) => {
      await grantDataScopes(engagement.id, scopes);
      engagementsMutate();
    },
    [engagementsMutate],
  );

  // Filter engagements by tab
  const filteredEngagements =
    currentTab === "all"
      ? engagements
      : engagements.filter((e) => e.status === currentTab);

  // Group counts for tabs
  const getTabCount = (status: string) => {
    if (status === "all") return engagements.length;
    return engagements.filter((e) => e.status === status).length;
  };

  const renderLoading = (
    <Stack spacing={2}>
      {[1, 2, 3].map((i) => (
        <Skeleton key={i} variant="rounded" height={180} />
      ))}
    </Stack>
  );

  const renderEmpty = (
    <EmptyContent
      title="Keine Partner-Engagements"
      description="Finden Sie passende BGM-Dienstleister im Marktplatz"
      imgUrl="/assets/illustrations/illustration-empty-content.svg"
      action={
        <Button
          component={RouterLink}
          href={paths.dashboard.marketplace?.root || "#"}
          variant="contained"
          startIcon={<Iconify icon="solar:add-circle-bold" />}
        >
          Partner finden
        </Button>
      }
      sx={{ py: 10 }}
    />
  );

  const renderTabs = (
    <Tabs
      value={currentTab}
      onChange={handleChangeTab}
      sx={{
        px: 2.5,
        boxShadow: (theme) => `inset 0 -2px 0 0 ${theme.vars.palette.divider}`,
      }}
    >
      {STATUS_TABS.map((tab) => {
        const count = getTabCount(tab.value);
        return (
          <Tab
            key={tab.value}
            value={tab.value}
            label={
              <Stack direction="row" spacing={1} alignItems="center">
                <Iconify icon={tab.icon} width={18} />
                <span>{tab.label}</span>
                {count > 0 && (
                  <Box
                    component="span"
                    sx={{
                      ml: 0.5,
                      px: 0.75,
                      py: 0.25,
                      borderRadius: 1,
                      bgcolor: "action.selected",
                      fontSize: "0.75rem",
                      fontWeight: "bold",
                    }}
                  >
                    {count}
                  </Box>
                )}
              </Stack>
            }
          />
        );
      })}
    </Tabs>
  );

  const renderStats = (
    <Stack direction="row" spacing={2} sx={{ mb: 3 }}>
      <Card sx={{ flex: 1 }}>
        <CardContent>
          <Stack direction="row" alignItems="center" spacing={2}>
            <Box
              sx={{
                width: 48,
                height: 48,
                borderRadius: 1.5,
                bgcolor: "primary.lighter",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Iconify
                icon="solar:handshake-bold"
                width={24}
                color="primary.main"
              />
            </Box>
            <Box>
              <Typography variant="h4">
                {
                  engagements.filter(
                    (e) => !["completed", "cancelled"].includes(e.status),
                  ).length
                }
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Aktive Engagements
              </Typography>
            </Box>
          </Stack>
        </CardContent>
      </Card>

      <Card sx={{ flex: 1 }}>
        <CardContent>
          <Stack direction="row" alignItems="center" spacing={2}>
            <Box
              sx={{
                width: 48,
                height: 48,
                borderRadius: 1.5,
                bgcolor: "warning.lighter",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Iconify
                icon="solar:inbox-in-bold"
                width={24}
                color="warning.main"
              />
            </Box>
            <Box>
              <Typography variant="h4">
                {engagements.filter((e) => e.status === "delivered").length}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Ergebnisse zur Integration
              </Typography>
            </Box>
          </Stack>
        </CardContent>
      </Card>

      <Card sx={{ flex: 1 }}>
        <CardContent>
          <Stack direction="row" alignItems="center" spacing={2}>
            <Box
              sx={{
                width: 48,
                height: 48,
                borderRadius: 1.5,
                bgcolor: "success.lighter",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
              }}
            >
              <Iconify
                icon="solar:check-circle-bold"
                width={24}
                color="success.main"
              />
            </Box>
            <Box>
              <Typography variant="h4">
                {engagements.filter((e) => e.status === "completed").length}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Abgeschlossene Projekte
              </Typography>
            </Box>
          </Stack>
        </CardContent>
      </Card>
    </Stack>
  );

  const renderList = (
    <Stack spacing={2}>
      {filteredEngagements.map((engagement) => (
        <EngagementCard
          key={engagement.id}
          engagement={engagement}
          onViewDetails={() => {
            // TODO: Navigate to detail view
            console.log("View details:", engagement.id);
          }}
          onActivate={() => handleActivate(engagement)}
          onCancel={() => handleCancel(engagement)}
          onComplete={() => handleComplete(engagement)}
          onGrantData={(scopes) => handleGrantData(engagement, scopes)}
        />
      ))}
    </Stack>
  );

  return (
    <Container maxWidth="lg">
      <CustomBreadcrumbs
        heading="Partner Engagements"
        links={[
          { name: "Dashboard", href: paths.dashboard.root },
          { name: "Marktplatz", href: paths.dashboard.marketplace?.root },
          { name: "Engagements" },
        ]}
        action={
          <Button
            component={RouterLink}
            href={paths.dashboard.marketplace?.root || "#"}
            variant="contained"
            startIcon={<Iconify icon="solar:add-circle-bold" />}
          >
            Partner finden
          </Button>
        }
        sx={{ mb: 3 }}
      />

      {renderStats}

      <Card>
        {renderTabs}
        <Box sx={{ p: 3 }}>
          {engagementsLoading && renderLoading}
          {!engagementsLoading &&
            filteredEngagements.length === 0 &&
            renderEmpty}
          {!engagementsLoading && filteredEngagements.length > 0 && renderList}
        </Box>
      </Card>
    </Container>
  );
}

