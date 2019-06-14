import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";
import {Icon} from "semantic-ui-react";
import ProjectStore from "../../../../Stores/Project.store";
import ProjectConstants from "../../../../Constants/Project.constants";

class MisalignedSegments extends Component {
    static propTypes = {
        job: PropTypes.shape({
            counters: PropTypes.shape({
                hideIndexesMap: PropTypes.array,
                misalignmentsIndexesMap: PropTypes.array
            }),
        }),
        close: PropTypes.func
    };

    constructor(props) {
        super(props);
        this.state = {
            actualSegmentSelected: 0,
            actualIndex: 0
        };
    }

    componentDidUpdate(prevProps) {
    }

    componentDidMount() {
        const {job: {counters: {misalignmentsIndexesMap}}} = this.props;
        ProjectActions.emitMisalignmentsData({
            actualSegmentSelected: misalignmentsIndexesMap[0],
            realRowIndex: misalignmentsIndexesMap[0]
        });
       /* ProjectStore.addListener(ProjectConstants.SEARCH_RESULTS, this.closeMisalignmentSegmentsNavigator);
        ProjectStore.addListener(ProjectConstants.HIDE_SEGMENTS_NAVIGATOR, this.closeMisalignmentSegmentsNavigator);*/
    }

    componentWillUnmount() {
        setTimeout(() => {
            this.resetHideSegmentsNavigator();
        }, 0);
       /* ProjectStore.removeListener(ProjectConstants.SEARCH_RESULTS, this.closeMisalignmentSegmentsNavigator);
        ProjectStore.removeListener(ProjectConstants.HIDE_SEGMENTS_NAVIGATOR, this.closeMisalignmentSegmentsNavigator);*/
    }

    render() {
        const {job: {counters: {misalignmentsIndexesMap}}} = this.props;
        const {actualSegmentSelected, actualIndex} = this.state;
        return (
            <div id="hide-segments">
                <span className="amount">{actualSegmentSelected + 1} / {misalignmentsIndexesMap.length}</span>
                <span className="controls">
                    <Icon className={"increment"} name='chevron up' onClick={() => this.onMisalignmentSegmentsChange("up")}/>
                    <Icon className={"decrement"} name='chevron down' onClick={() => this.onMisalignmentSegmentsChange("down")}/>
                    <Icon className={"close"} name='x' onClick={() => this.closeMisalignmentSegmentsNavigator()}/>
                </span>
            </div>
        );
    }

    onMisalignmentSegmentsChange = (direction) => {
        const {actualSegmentSelected, actualIndex} = this.state;
        const {job: {counters: {misalignmentsIndexesMap}}} = this.props;
        let segmentSelected = actualSegmentSelected;
        let navigatorIndex = actualIndex;

        if (direction === "down") {
            navigatorIndex = ++navigatorIndex;
            segmentSelected = this.mod(navigatorIndex, misalignmentsIndexesMap.length);
        } else {
            navigatorIndex = --navigatorIndex;
            segmentSelected = this.mod(navigatorIndex, misalignmentsIndexesMap.length);
        }

        ProjectActions.emitMisalignmentsData({
            actualSegmentSelected: segmentSelected,
            realRowIndex: misalignmentsIndexesMap[segmentSelected]
        });

        console.log("segment selected: ", segmentSelected);
        console.log("actualIndex: ", navigatorIndex);
        console.log("misalignmentsIndexesMap map real row index: ", misalignmentsIndexesMap[segmentSelected]);

        this.setState({
            actualSegmentSelected: segmentSelected,
            actualIndex: navigatorIndex,
        });
    };

    // handling module
    mod = (n, m) => {
        return ((n % m) + m) % m;
    };

    resetHideSegmentsNavigator = () => {
        ProjectActions.emitMisalignmentsData({
            actualSegmentSelected: null,
            realRowIndex: null
        });
    };

    closeMisalignmentSegmentsNavigator = () => {
        setTimeout(() => {
            this.resetHideSegmentsNavigator();
        }, 0);
        this.props.close();
    }
}

export default MisalignedSegments;
