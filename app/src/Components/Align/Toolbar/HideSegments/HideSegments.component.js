import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";
import {Icon} from "semantic-ui-react";
import ProjectStore from "../../../../Stores/Project.store";
import ProjectConstants from "../../../../Constants/Project.constants";

class HideSegments extends Component {
    static propTypes = {
        job: PropTypes.shape({
            counters: PropTypes.shape({
                hideIndexesMap: PropTypes.array,
                missAlignmentsIndexesMap: PropTypes.array
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
        const {job: {counters: {hideIndexesMap}}} = this.props;
        const {closeHideSegmentsNavigator} = this;

        if( !hideIndexesMap.length ){
            closeHideSegmentsNavigator()
        }
    }

    componentDidMount() {
        const {job: {counters: {hideIndexesMap}}} = this.props;
        ProjectActions.emitHideNavigatorData({
            actualSegmentSelected: hideIndexesMap[0],
            realRowIndex: hideIndexesMap[0]
        });
    }

    componentWillUnmount() {
        setTimeout(() => {
            this.resetHideSegmentsNavigator();
        }, 0);
    }

    render() {
        const {job: {counters: {hideIndexesMap}}} = this.props;
        const {actualSegmentSelected, actualIndex} = this.state;
        return (
            <div id="hide-segments">
                <span className="amount">
                    <span>{actualSegmentSelected + 1}</span> / {hideIndexesMap.length}
                </span>
                <span className="controls">
                    <Icon className={"increment"} name='chevron up' onClick={() => this.onHideNavigationChange("up")}/>
                    <Icon className={"decrement"} name='chevron down' onClick={() => this.onHideNavigationChange("down")}/>
                    <Icon className={"close"} name='x' onClick={() => this.closeHideSegmentsNavigator()}/>
                </span>
            </div>
        );
    }

    onHideNavigationChange = (direction) => {
        const {actualSegmentSelected, actualIndex} = this.state;
        const {job: {counters: {hideIndexesMap}}} = this.props;
        let segmentSelected = actualSegmentSelected;
        let navigatorIndex = actualIndex;

        if (direction === "down") {
            navigatorIndex = ++navigatorIndex;
            segmentSelected = this.mod(navigatorIndex, hideIndexesMap.length);
        } else {
            navigatorIndex = --navigatorIndex;
            segmentSelected = this.mod(navigatorIndex, hideIndexesMap.length);
        }

        ProjectActions.emitHideNavigatorData({
            actualSegmentSelected: segmentSelected,
            realRowIndex: hideIndexesMap[segmentSelected]
        });

        console.log("segment selected: ", segmentSelected);
        console.log("actualIndex: ", navigatorIndex);
        console.log("hideindex map real row index: ", hideIndexesMap[segmentSelected]);

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
        ProjectActions.emitHideNavigatorData({
            actualSegmentSelected: null,
            realRowIndex: null
        });
    };

    closeHideSegmentsNavigator = () => {
        setTimeout(() => {
            this.resetHideSegmentsNavigator();
        }, 0);
        this.props.close();
    }
}

export default HideSegments;
