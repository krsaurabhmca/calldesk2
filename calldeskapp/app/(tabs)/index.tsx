import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, StyleSheet, RefreshControl, TouchableOpacity, Dimensions, ActivityIndicator } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { apiCall } from '../../services/api';
import { getUser } from '../../services/auth';
import { Users, CalendarClock, CheckCircle2, TrendingUp, PhoneCall, ArrowUpRight, Clock, Target, ChevronRight, BarChart3, ShieldCheck, Zap, PlusCircle, UserPlus, RefreshCcw, LayoutGrid, Award, Briefcase } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';

const { width } = Dimensions.get('window');

export default function Dashboard() {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<any>(null);
    const [user, setUser] = useState<any>(null);
    const [refreshing, setRefreshing] = useState(false);
    const router = useRouter();

    const fetchData = async () => {
        if (!refreshing) setLoading(true);
        const userData = await getUser();
        setUser(userData);

        const res = await apiCall('dashboard.php');
        if (res.success) {
            setData(res.data);
        }
        setLoading(false);
    };

    useFocusEffect(
        useCallback(() => {
            fetchData();
        }, [])
    );

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchData();
        setRefreshing(false);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Converted': return '#10b981';
            case 'Interested': return '#6366f1';
            case 'Lost': return '#ef4444';
            case 'Follow-up': return '#f59e0b';
            default: return '#94a3b8';
        }
    };

    if (loading && !refreshing) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
            </View>
        );
    }

    // Role detection: prioritize API data, fallback to SecureStore user role
    const effectiveRole = data?.role || user?.role;
    const isAdmin = effectiveRole?.toLowerCase() === 'admin';
    const stats = data?.stats;

    return (
        <SafeAreaView style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.greeting}>Welcome back,</Text>
                    <Text style={styles.userName}>{user?.name || 'User'}</Text>
                </View>
                <View style={[styles.roleBadge, { backgroundColor: isAdmin ? '#eef2ff' : '#f0fdf4' }]}>
                    {isAdmin ? <ShieldCheck size={14} color="#6366f1" /> : <Zap size={14} color="#10b981" />}
                    <Text style={[styles.roleText, { color: isAdmin ? '#6366f1' : '#10b981' }]}>
                        {isAdmin ? 'Admin' : 'Executive'}
                    </Text>
                </View>
                <TouchableOpacity style={styles.addShortcut} onPress={() => router.push('/(tabs)/leads')}>
                    <PlusCircle size={32} color="#6366f1" />
                </TouchableOpacity>
            </View>

            <ScrollView
                showsVerticalScrollIndicator={false}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                {isAdmin ? (
                    /* ADMIN VIEW: STRATEGIC DASHBOARD */
                    <View style={styles.content}>
                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Strategic Insights</Text>
                            <View style={styles.statsGrid}>
                                <TouchableOpacity style={[styles.mainStatCard, { backgroundColor: '#6366f1' }]} onPress={() => router.push('/leads')}>
                                    <View style={styles.statIconBox}>
                                        <Users size={20} color="#fff" />
                                    </View>
                                    <Text style={styles.statLargeVal}>{stats?.total_leads || 0}</Text>
                                    <Text style={styles.statMiniLabel}>Total Leads</Text>
                                    <View style={styles.statGrowthPulse} />
                                </TouchableOpacity>
                                <View style={[styles.mainStatCard, { backgroundColor: '#f59e0b' }]}>
                                    <View style={styles.statIconBox}>
                                        <ArrowUpRight size={20} color="#fff" />
                                    </View>
                                    <Text style={styles.statLargeVal}>{stats?.today_leads || 0}</Text>
                                    <Text style={styles.statMiniLabel}>New Today</Text>
                                </View>
                            </View>

                            <View style={[styles.statsGrid, { marginTop: 12 }]}>
                                <View style={[styles.mainStatCard, { backgroundColor: '#10b981' }]}>
                                    <View style={styles.statIconBox}>
                                        <PhoneCall size={20} color="#fff" />
                                    </View>
                                    <Text style={styles.statLargeVal}>{stats?.today_calls || 0}</Text>
                                    <Text style={styles.statMiniLabel}>Call Activity</Text>
                                </View>
                                <View style={[styles.mainStatCard, { backgroundColor: '#ec4899' }]}>
                                    <View style={styles.statIconBox}>
                                        <Award size={20} color="#fff" />
                                    </View>
                                    <Text style={styles.statLargeVal}>{stats?.converted_leads || 0}</Text>
                                    <Text style={styles.statMiniLabel}>Conversions</Text>
                                </View>
                            </View>
                        </View>

                        <TouchableOpacity style={styles.premiumBanner} onPress={() => router.push('/reports')}>
                            <View style={styles.bannerContent}>
                                <View style={styles.bannerIconBox}>
                                    <BarChart3 size={24} color="#6366f1" />
                                </View>
                                <View>
                                    <Text style={styles.bannerTitle}>Analytics Dashboard</Text>
                                    <Text style={styles.bannerSub}>Analyze productivity and conversion ratios</Text>
                                </View>
                            </View>
                            <ChevronRight size={20} color="#6366f1" />
                        </TouchableOpacity>

                        <View style={styles.section}>
                            <View style={styles.sectionHeader}>
                                <Text style={styles.sectionTitle}>Real-time Team Pulse</Text>
                                <TouchableOpacity onPress={() => router.push('/users')}>
                                    <Text style={styles.seeAll}>Organization</Text>
                                </TouchableOpacity>
                            </View>
                            {data?.executive_performance?.length > 0 ? (
                                data.executive_performance.map((exec: any) => (
                                    <TouchableOpacity key={exec.id} style={styles.premiumExecCard} onPress={() => router.push('/users')}>
                                        <View style={styles.execInfoRow}>
                                            <View style={styles.execAvatarPremium}>
                                                <Text style={styles.execAvatarTextPremium}>{(exec.name || 'U').charAt(0).toUpperCase()}</Text>
                                                <View style={[styles.onlineIndicator, { backgroundColor: exec.total_calls > 0 ? '#10b981' : '#cbd5e1' }]} />
                                            </View>
                                            <View style={{ flex: 1 }}>
                                                <Text style={styles.execNamePremium}>{exec.name}</Text>
                                                <View style={styles.execSubRow}>
                                                    <Clock size={12} color="#94a3b8" />
                                                    <Text style={styles.execMetaPremium}>{exec.pending_tasks} Pending Tasks</Text>
                                                </View>
                                            </View>
                                            <View style={styles.execScoreBox}>
                                                <Text style={styles.execScoreText}>{exec.total_calls}</Text>
                                                <Text style={styles.execScoreLabel}>Calls</Text>
                                            </View>
                                        </View>
                                        <View style={styles.callSplitLine}>
                                            <View style={[styles.splitPart, { backgroundColor: '#10b981', flex: Math.max(0.1, exec.incoming_calls / (exec.total_calls || 1)) }]} />
                                            <View style={[styles.splitPart, { backgroundColor: '#6366f1', flex: Math.max(0.1, exec.outgoing_calls / (exec.total_calls || 1)) }]} />
                                            <View style={[styles.splitPart, { backgroundColor: '#ef4444', flex: Math.max(0.1, exec.missed_calls / (exec.total_calls || 1)) }]} />
                                        </View>
                                    </TouchableOpacity>
                                ))
                            ) : (
                                <Text style={styles.empty}>No team activity recorded today.</Text>
                            )}
                        </View>
                    </View>
                ) : (
                    /* EXECUTIVE VIEW: PERFORMANCE DASHBOARD */
                    <View style={styles.content}>
                        <View style={styles.section}>
                            <View style={styles.executiveHeroCard}>
                                <View style={styles.heroHeader}>
                                    <View>
                                        <Text style={styles.heroGreeting}>Target Tracking</Text>
                                        <Text style={styles.heroSubtitle}>{stats?.completed_tasks} of {stats?.today_tasks || 0} tasks completed</Text>
                                    </View>
                                    <View style={styles.heroIconBox}>
                                        <Target size={24} color="#fff" />
                                    </View>
                                </View>
                                <View style={styles.heroProgressContainer}>
                                    <View style={styles.heroProgressBar}>
                                        <View style={[styles.heroProgressFill, { width: `${stats?.performance_percent || 0}%` }]} />
                                    </View>
                                    <Text style={styles.heroPercentText}>{stats?.performance_percent || 0}%</Text>
                                </View>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Today's Momentum</Text>
                            <View style={styles.momentumGrid}>
                                <View style={styles.momentumCard}>
                                    <Text style={styles.momentumVal}>{stats?.my_leads || 0}</Text>
                                    <Text style={styles.momentumLabel}>Assigned</Text>
                                    <Users size={16} color="#6366f1" style={styles.momentumIcon} />
                                </View>
                                <View style={[styles.momentumCard, { borderColor: '#10b981' }]}>
                                    <Text style={[styles.momentumVal, { color: '#10b981' }]}>{stats?.my_converted || 0}</Text>
                                    <Text style={styles.momentumLabel}>Converted</Text>
                                    <Award size={16} color="#10b981" style={styles.momentumIcon} />
                                </View>
                                <TouchableOpacity style={[styles.momentumCard, { borderColor: '#f59e0b' }]} onPress={() => router.push('/tasks')}>
                                    <Text style={[styles.momentumVal, { color: '#f59e0b' }]}>{stats?.pending_tasks || 0}</Text>
                                    <Text style={styles.momentumLabel}>To-Do</Text>
                                    <CalendarClock size={16} color="#f59e0b" style={styles.momentumIcon} />
                                </TouchableOpacity>
                            </View>
                        </View>

                        <View style={styles.quickToolsBar}>
                            <TouchableOpacity style={styles.toolBtn} onPress={() => router.push({ pathname: '/leads', params: { showAdd: 'true' } })}>
                                <PlusCircle size={18} color="#fff" />
                                <Text style={styles.toolBtnText}>Add Lead</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={[styles.toolBtn, { backgroundColor: '#10b981' }]} onPress={() => router.push('/calls')}>
                                <RefreshCcw size={18} color="#fff" />
                                <Text style={styles.toolBtnText}>Sync Calls</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                )}

                {/* Shared Section: Recent Activity */}
                <View style={[styles.section, { marginBottom: 30 }]}>
                    <View style={styles.sectionHeader}>
                        <Text style={styles.sectionTitle}>{isAdmin ? 'All Leads' : 'My Leads'}</Text>
                        <TouchableOpacity onPress={() => router.push('/leads')}>
                            <Text style={styles.seeAll}>View All</Text>
                        </TouchableOpacity>
                    </View>

                    {data?.recent_leads?.length > 0 ? (
                        data.recent_leads.map((item: any) => (
                            <TouchableOpacity key={item.id} style={styles.prospectItem} onPress={() => router.push('/leads')}>
                                <View style={[styles.statusStrip, { backgroundColor: getStatusColor(item.status) }]} />
                                <View style={styles.prospectInfo}>
                                    <Text style={styles.prospectName}>{item.name}</Text>
                                    <Text style={styles.prospectMeta}>
                                        {item.mobile} {isAdmin && item.assigned_to_name ? `• ${item.assigned_to_name}` : ''}
                                    </Text>
                                </View>
                                <View style={[styles.statusBadge, { borderColor: getStatusColor(item.status) }]}>
                                    <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>{item.status}</Text>
                                </View>
                            </TouchableOpacity>
                        ))
                    ) : (
                        <Text style={styles.empty}>No recent leads found</Text>
                    )}
                </View>
            </ScrollView>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f8fafc' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, paddingTop: 10, paddingBottom: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    greeting: { fontSize: 13, color: '#94a3b8', fontWeight: '600' },
    userName: { fontSize: 24, fontWeight: '900', color: '#0f172a' },
    roleBadge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8, gap: 4 },
    roleText: { fontSize: 10, fontWeight: '800', textTransform: 'uppercase' },
    content: { paddingTop: 20 },
    section: { paddingHorizontal: 20, marginBottom: 25 },
    sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
    sectionTitle: { fontSize: 18, fontWeight: '900', color: '#0f172a' },
    seeAll: { fontSize: 13, fontWeight: '700', color: '#6366f1' },
    statsGrid: { flexDirection: 'row', gap: 12 },
    mainStatCard: { flex: 1, padding: 20, borderRadius: 28, elevation: 6, shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.15, shadowRadius: 10, position: 'relative', overflow: 'hidden' },
    statIconBox: { width: 36, height: 36, borderRadius: 12, backgroundColor: 'rgba(255,255,255,0.25)', justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
    statLargeVal: { fontSize: 26, fontWeight: '900', color: '#fff' },
    statMiniLabel: { fontSize: 11, color: 'rgba(255,255,255,0.85)', fontWeight: '700', textTransform: 'uppercase', marginTop: 2 },
    statGrowthPulse: { position: 'absolute', right: -10, top: -10, width: 60, height: 60, borderRadius: 30, backgroundColor: 'rgba(255,255,255,0.1)' },
    premiumBanner: { marginHorizontal: 20, backgroundColor: '#fff', padding: 20, borderRadius: 24, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 25, borderWidth: 1, borderColor: '#e2e8f0', elevation: 2 },
    bannerContent: { flexDirection: 'row', alignItems: 'center', gap: 16 },
    bannerIconBox: { width: 48, height: 48, borderRadius: 16, backgroundColor: '#eef2ff', justifyContent: 'center', alignItems: 'center' },
    bannerTitle: { fontSize: 16, fontWeight: '800', color: '#1e293b' },
    bannerSub: { fontSize: 12, color: '#64748b', marginTop: 2 },
    premiumExecCard: { backgroundColor: '#fff', borderRadius: 24, padding: 18, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9', elevation: 1 },
    execInfoRow: { flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 16 },
    execAvatarPremium: { width: 46, height: 46, borderRadius: 16, backgroundColor: '#f0f4ff', justifyContent: 'center', alignItems: 'center', position: 'relative' },
    onlineIndicator: { position: 'absolute', right: -2, bottom: -2, width: 12, height: 12, borderRadius: 6, borderWidth: 2, borderColor: '#fff' },
    execAvatarTextPremium: { color: '#6366f1', fontWeight: '900', fontSize: 18 },
    execNamePremium: { fontSize: 17, fontWeight: '800', color: '#1e293b' },
    execSubRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4 },
    execMetaPremium: { fontSize: 12, color: '#94a3b8', fontWeight: '600' },
    execScoreBox: { paddingHorizontal: 12, alignItems: 'center', borderLeftWidth: 1, borderLeftColor: '#f1f5f9' },
    execScoreText: { fontSize: 18, fontWeight: '900', color: '#0f172a' },
    execScoreLabel: { fontSize: 10, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase' },
    callSplitLine: { height: 4, width: '100%', flexDirection: 'row', borderRadius: 2, overflow: 'hidden' },
    splitPart: { height: '100%' },
    executiveHeroCard: { backgroundColor: '#6366f1', padding: 24, borderRadius: 32, elevation: 8, shadowColor: '#6366f1', shadowOffset: { width: 0, height: 6 }, shadowOpacity: 0.3, shadowRadius: 15 },
    heroHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 },
    heroGreeting: { fontSize: 18, fontWeight: '800', color: '#fff' },
    heroSubtitle: { fontSize: 14, color: 'rgba(255,255,255,0.85)', marginTop: 4 },
    heroIconBox: { width: 44, height: 44, borderRadius: 14, backgroundColor: 'rgba(255,255,255,0.2)', justifyContent: 'center', alignItems: 'center' },
    heroProgressContainer: { gap: 12 },
    heroProgressBar: { height: 12, backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 6, overflow: 'hidden' },
    heroProgressFill: { height: '100%', backgroundColor: '#fff', borderRadius: 6 },
    heroPercentText: { fontSize: 14, fontWeight: '900', color: '#fff', textAlign: 'right' },
    momentumGrid: { flexDirection: 'row', gap: 12 },
    momentumCard: { flex: 1, backgroundColor: '#fff', padding: 16, borderRadius: 22, borderWidth: 1.5, borderColor: '#eef2ff', position: 'relative' },
    momentumVal: { fontSize: 20, fontWeight: '900', color: '#0f172a' },
    momentumLabel: { fontSize: 11, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase', marginTop: 4 },
    momentumIcon: { position: 'absolute', right: 12, top: 12, opacity: 0.2 },
    quickToolsBar: { flexDirection: 'row', paddingHorizontal: 20, gap: 12, marginBottom: 25 },
    toolBtn: { flex: 1, backgroundColor: '#6366f1', paddingVertical: 14, borderRadius: 18, flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8, elevation: 4 },
    toolBtnText: { color: '#fff', fontSize: 14, fontWeight: '800' },
    prospectItem: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', padding: 18, borderRadius: 24, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9', elevation: 1 },
    statusStrip: { width: 4, height: 32, borderRadius: 2, marginRight: 16 },
    prospectInfo: { flex: 1 },
    prospectName: { fontSize: 17, fontWeight: '800', color: '#1e293b' },
    prospectMeta: { fontSize: 12, color: '#94a3b8', marginTop: 4, fontWeight: '500' },
    statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 10, borderWidth: 1.5 },
    statusText: { fontSize: 10, fontWeight: '900', textTransform: 'uppercase' },
    empty: { textAlign: 'center', color: '#94a3b8', marginTop: 20, fontSize: 14 },
    addShortcut: { marginLeft: 12 }
});
