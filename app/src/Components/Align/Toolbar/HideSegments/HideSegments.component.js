import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";

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

    componentDidUpdate(prevProps) {}

    componentDidMount() {
        ProjectActions.emitHideNavigatorData({
            actualSegmentSelected: 0,
            realRowIndex: 0
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
            <div id="search">
                <div>  {actualSegmentSelected+1} / {hideIndexesMap.length}
                    <span onClick={() => this.onHideNavigationChange("up")}> Up </span>
                    <span onClick={() => this.onHideNavigationChange("down")}> Down </span>
                    <span onClick={() =>  this.closeHideSegmentsNavigator()}> X </span>
                </div>
            </div>
        );
    }

    onHideNavigationChange = (direction) => {
        const { actualSegmentSelected, actualIndex} = this.state;
        const {job: {counters: {hideIndexesMap}}} = this.props;
        let segmentSelected = actualSegmentSelected;
        let navigatorIndex = actualIndex;

        if(direction === "up"){
            navigatorIndex = ++navigatorIndex;
            segmentSelected = this.mod(navigatorIndex,hideIndexesMap.length);
        }else{
            navigatorIndex = --navigatorIndex;
            segmentSelected = this.mod(navigatorIndex,hideIndexesMap.length);
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
        this.resetHideSegmentsNavigator();
        this.props.close();
    }
}

export default HideSegments;
